<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Services\WhatsAppOtpService;
use Database\Seeders\RoomSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RapiEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // 1. DATABASE SEEDERS & MASTER ROOMS
    // =========================================================================
    public function test_room_seeder_provisions_realistic_school_rooms_and_api_serves_them(): void
    {
        $this->seed(RoomSeeder::class);

        $expectedRooms = [
            'Lab 1',
            'Lab 21',
            'Ruang Rapat Wijaya Kusuma',
            'Ruang 32',
            'Masjid Lantai Bawah',
            'Masjid Lantai Atas',
        ];

        foreach ($expectedRooms as $roomName) {
            $this->assertDatabaseHas('rooms', [
                'room_name' => $roomName,
                'is_active' => true,
            ]);
        }

        $this->assertSame(21, Room::where('room_type', 'laboratorium')->where('is_active', true)->count());
        $this->assertSame(32, Room::where('room_type', 'kelas')->where('is_active', true)->count());
        $this->assertSame(32, Room::where('room_type', 'kelas')->where('is_active', true)->where('room_name', 'like', 'Ruang %')->count());
        $this->assertSame(2, Room::where('room_type', 'ruang_rapat')->where('is_active', true)->count());
        $this->assertSame(2, Room::where('room_type', 'masjid')->where('is_active', true)->count());
        $this->assertSame(135, Room::where('room_name', 'Ruang Rapat Wijaya Kusuma')->value('capacity'));
        $this->assertSame('Mas Arif', Room::where('room_name', 'Ruang Rapat Wijaya Kusuma')->first()->pic->fullname);
        $this->assertSame(40, Room::where('room_name', 'Ruang Rapat DAK')->value('capacity'));
        $this->assertSame('Pak Haris', Room::where('room_name', 'Ruang Rapat DAK')->first()->pic->fullname);

        Room::create([
            'room_name' => 'Classroom 1',
            'room_type' => 'kelas',
            'capacity' => 36,
            'location' => 'Legacy classroom',
            'pic_id' => User::where('username', 'sarpras')->value('id'),
            'is_active' => true,
        ]);
        $this->seed(RoomSeeder::class);
        $this->assertDatabaseHas('rooms', ['room_name' => 'Classroom 1', 'is_active' => false]);
        $this->assertSame(32, Room::where('room_type', 'kelas')->where('is_active', true)->count());

        // Ensure API endpoint /api/rooms returns active rooms
        $response = $this->getJson('/api/rooms')->assertOk();
        $roomNames = collect($response->json('data'))->pluck('room_name');

        foreach ($expectedRooms as $roomName) {
            $this->assertTrue($roomNames->contains($roomName), "Room {$roomName} should be present in /api/rooms");
        }
    }

    public function test_admin_room_selects_group_rooms_and_show_capacity_and_pic(): void
    {
        $this->seed(RoomSeeder::class);
        $adminRole = Role::firstOrCreate(['role_name' => 'Admin']);
        $admin = User::create([
            'username' => 'room_select_admin',
            'password' => Hash::make('password123'),
            'fullname' => 'Room Select Admin',
            'phone_number' => '081234567890',
            'role_id' => $adminRole->id,
        ]);

        $this->withoutVite()->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('<optgroup label="Ruang Rapat">', false)
            ->assertSee('<optgroup label="Laboratorium">', false)
            ->assertSee('<optgroup label="Ruang Kelas">', false)
            ->assertSee('<optgroup label="Masjid">', false)
            ->assertSee('Ruang Rapat Wijaya Kusuma')
            ->assertSee('kapasitas 135')
            ->assertSee('PIC Mas Arif')
            ->assertSee('Ruang 1')
            ->assertSee('khusus akhir pekan')
            ->assertSee('PIC PIC Sarpras')
            ->assertSee('<b>'.Room::where('is_active', true)->count().'</b><span>Ruangan</span>', false)
            ->assertDontSee('Lab Komputer');
    }

    public function test_pic_dashboard_only_lists_and_counts_rooms_assigned_to_that_pic(): void
    {
        $this->seed(RoomSeeder::class);
        $pic = User::where('username', 'arif')->firstOrFail();
        $assignedRooms = Room::where('pic_id', $pic->id)->where('is_active', true)->count();
        $allActiveRooms = Room::where('is_active', true)->count();

        $response = $this->withoutVite()->actingAs($pic)->get('/pic/dashboard')
            ->assertOk()
            ->assertSee('<b>'.$allActiveRooms.'</b><span>Ruangan</span>', false)
            ->assertViewHas('assignedRooms', fn ($rooms) => $rooms->count() === $assignedRooms)
            ->assertViewHas('bookingRooms', fn ($rooms) => $rooms->count() === $allActiveRooms);

        $document = new \DOMDocument();
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);

        $unassignedLab = Room::where('room_name', 'Lab 1')->firstOrFail();
        $assignedMeetingRoom = Room::where('room_name', 'Ruang Rapat Wijaya Kusuma')->firstOrFail();

        $this->assertSame(1, $xpath->query('//select[@id="room_pinjam"]/optgroup/option[@value="'.$unassignedLab->id.'"]')->length);
        $this->assertSame(0, $xpath->query('//select[@id="room_rusak"]/optgroup/option[@value="'.$unassignedLab->id.'"]')->length);
        $this->assertSame(1, $xpath->query('//select[@id="room_pinjam"]/optgroup/option[@value="'.$assignedMeetingRoom->id.'"]')->length);
        $this->assertSame(1, $xpath->query('//select[@id="room_rusak"]/optgroup/option[@value="'.$assignedMeetingRoom->id.'"]')->length);
    }

    // =========================================================================
    // 2. ADMIN USER MANAGEMENT (CRUD)
    // =========================================================================
    public function test_admin_can_manage_users_via_web_dashboard(): void
    {
        $adminRole = Role::firstOrCreate(['role_name' => 'Admin']);
        $guruRole = Role::firstOrCreate(['role_name' => 'Guru']);
        $picRole = Role::firstOrCreate(['role_name' => 'PIC']);
        $organisasiRole = Role::firstOrCreate(['role_name' => 'Organisasi']);

        $admin = User::create([
            'username'     => 'superadmin',
            'password'     => Hash::make('password123'),
            'fullname'     => 'Super Administrator',
            'phone_number' => '081234567890',
            'role_id'      => $adminRole->id,
        ]);

        // 1. View user management table in admin dashboard
        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Kelola Pengguna Sistem')
            ->assertSee('superadmin')
            ->assertSee('Super Administrator');

        // 2. Store new user (e.g. Organisasi)
        $this->actingAs($admin)->post('/admin/users', [
            'username'     => 'osis_baru',
            'fullname'     => 'Pengurus OSIS Baru',
            'phone_number' => '081299887766',
            'role_id'      => $organisasiRole->id,
            'password'     => 'osis12345',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'username'     => 'osis_baru',
            'fullname'     => 'Pengurus OSIS Baru',
            'phone_number' => '081299887766',
            'role_id'      => $organisasiRole->id,
        ]);

        $newUser = User::where('username', 'osis_baru')->firstOrFail();
        $this->assertTrue(Hash::check('osis12345', $newUser->password));

        // 3. Edit user details and role
        $this->actingAs($admin)->put('/admin/users/' . $newUser->id, [
            'username'     => 'osis_edit',
            'fullname'     => 'Pengurus OSIS Diedit',
            'phone_number' => '081211112222',
            'role_id'      => $guruRole->id,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id'           => $newUser->id,
            'username'     => 'osis_edit',
            'fullname'     => 'Pengurus OSIS Diedit',
            'phone_number' => '081211112222',
            'role_id'      => $guruRole->id,
        ]);

        // 4. Reset user password
        $this->actingAs($admin)->post('/admin/users/' . $newUser->id . '/reset-password', [
            'password' => 'newpassword123',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertTrue(Hash::check('newpassword123', $newUser->fresh()->password));

        // 5. Delete user
        $this->actingAs($admin)->delete('/admin/users/' . $newUser->id)
            ->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $newUser->id]);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $adminRole = Role::firstOrCreate(['role_name' => 'Admin']);
        $admin = User::create([
            'username'     => 'admin_self',
            'password'     => Hash::make('password123'),
            'fullname'     => 'Admin Self',
            'phone_number' => '081234567890',
            'role_id'      => $adminRole->id,
        ]);

        $this->actingAs($admin)->delete('/admin/users/' . $admin->id)
            ->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_manage_rooms_via_web_dashboard(): void
    {
        $adminRole = Role::firstOrCreate(['role_name' => 'Admin']);
        $picRole = Role::firstOrCreate(['role_name' => 'PIC']);
        $guruRole = Role::firstOrCreate(['role_name' => 'Guru']);

        $admin = User::create([
            'username'     => 'admin_room_mgr',
            'password'     => Hash::make('password123'),
            'fullname'     => 'Admin Room Manager',
            'phone_number' => '081234567890',
            'role_id'      => $adminRole->id,
        ]);

        $pic = User::create([
            'username'     => 'pic_room_mgr',
            'password'     => Hash::make('password123'),
            'fullname'     => 'PIC Room Manager',
            'phone_number' => '081234567891',
            'role_id'      => $picRole->id,
        ]);

        $guru = User::create([
            'username'     => 'guru_non_admin',
            'password'     => Hash::make('password123'),
            'fullname'     => 'Guru Non Admin',
            'phone_number' => '081234567892',
            'role_id'      => $guruRole->id,
        ]);

        // 1. View room management section in admin dashboard
        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Kelola Ruangan');

        // 2. Non-admin (Guru) is forbidden from managing rooms
        $this->actingAs($guru)->post('/admin/rooms', [
            'room_name' => 'Lab Unauthorized',
            'room_type' => 'laboratorium',
            'capacity'  => 30,
            'location'  => 'Gedung A',
            'pic_id'    => $pic->id,
        ])->assertForbidden();

        // 3. Admin creates a new room
        $this->actingAs($admin)->post('/admin/rooms', [
            'room_name' => 'Studio Robotik Baru',
            'room_type' => 'laboratorium',
            'capacity'  => 36,
            'location'  => 'Gedung C Lantai 3',
            'pic_id'    => $pic->id,
            'is_active' => '1',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('rooms', [
            'room_name' => 'Studio Robotik Baru',
            'room_type' => 'laboratorium',
            'capacity'  => 36,
            'location'  => 'Gedung C Lantai 3',
            'pic_id'    => $pic->id,
            'is_active' => 1,
        ]);

        $room = Room::where('room_name', 'Studio Robotik Baru')->firstOrFail();

        // 4. Admin updates room details
        $this->actingAs($admin)->put('/admin/rooms/' . $room->id, [
            'room_name' => 'Studio Robotik Utama',
            'room_type' => 'laboratorium',
            'capacity'  => 40,
            'location'  => 'Gedung C Lantai 3 (Ruang 301)',
            'pic_id'    => $pic->id,
            'is_active' => '1',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('rooms', [
            'id'        => $room->id,
            'room_name' => 'Studio Robotik Utama',
            'capacity'  => 40,
        ]);

        // 5. Admin deletes room
        $this->actingAs($admin)->delete('/admin/rooms/' . $room->id)
            ->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    public function test_admin_cannot_change_pic_role_if_managing_active_rooms(): void
    {
        $adminRole = Role::firstOrCreate(['role_name' => 'Admin']);
        $picRole = Role::firstOrCreate(['role_name' => 'PIC']);
        $guruRole = Role::firstOrCreate(['role_name' => 'Guru']);

        $admin = User::create([
            'username'     => 'admin_role',
            'password'     => Hash::make('password123'),
            'fullname'     => 'Admin Role',
            'phone_number' => '081234567890',
            'role_id'      => $adminRole->id,
        ]);

        $pic = User::create([
            'username'     => 'pic_active',
            'password'     => Hash::make('password123'),
            'fullname'     => 'PIC Active',
            'phone_number' => '081234567891',
            'role_id'      => $picRole->id,
        ]);

        Room::create([
            'room_name' => 'Ruang Bertanggung Jawab',
            'capacity'  => 30,
            'location'  => 'Gedung A',
            'pic_id'    => $pic->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->put('/admin/users/' . $pic->id, [
            'username'     => 'pic_active',
            'fullname'     => 'PIC Active',
            'phone_number' => '081234567891',
            'role_id'      => $guruRole->id,
        ])->assertSessionHasErrors('role_id');

        $this->assertSame($picRole->id, $pic->fresh()->role_id);
    }

    // =========================================================================
    // 3. WHATSAPP ESCALATION FOR PIC ROOMS
    // =========================================================================
    public function test_admin_dashboard_provides_prefilled_whatsapp_escalation_link(): void
    {
        $adminRole = Role::firstOrCreate(['role_name' => 'Admin']);
        $picRole = Role::firstOrCreate(['role_name' => 'PIC']);
        $guruRole = Role::firstOrCreate(['role_name' => 'Guru']);

        $admin = User::create([
            'username'     => 'admin_wa',
            'password'     => Hash::make('password123'),
            'fullname'     => 'Admin WA',
            'phone_number' => '081234567890',
            'role_id'      => $adminRole->id,
        ]);

        $pic = User::create([
            'username'     => 'pic_ruang',
            'password'     => Hash::make('password123'),
            'fullname'     => 'Budi Sarpras',
            'phone_number' => '081234567888',
            'role_id'      => $picRole->id,
        ]);

        $reporter = User::create([
            'username'     => 'guru_pelapor',
            'password'     => Hash::make('password123'),
            'fullname'     => 'Siti Rahma',
            'phone_number' => '081234567899',
            'role_id'      => $guruRole->id,
        ]);

        $room = Room::create([
            'room_name' => 'Lab Komputer 1',
            'capacity'  => 36,
            'location'  => 'Gedung Komputer Lt. 1',
            'pic_id'    => $pic->id,
            'is_active' => true,
        ]);

        $complaint = Complaint::create([
            'room_id'       => $room->id,
            'reporter_id'   => $reporter->id,
            'description'   => 'AC ruangan bocor dan menetes ke meja siswa',
            'photo_path'    => 'complaints/ac.jpg',
            'status_repair' => 'Belum Diperbaiki',
            'reported_at'   => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard')->assertOk();

        // Check for Reporter name in the complaints list
        $response->assertSee('Siti Rahma');
        $response->assertSee('Lab Komputer 1');
        $response->assertSee('AC ruangan bocor dan menetes ke meja siswa');

        // Check that pre-filled direct WhatsApp link is present
        $expectedWaNumber = '6281234567888';
        $response->assertSee('https://wa.me/' . $expectedWaNumber . '?text=', false);
    }

    // =========================================================================
    // 4. FORGOT PASSWORD VIA WHATSAPP OTP SYSTEM
    // =========================================================================
    public function test_password_reset_is_available_via_web_and_api_whatsapp_otp(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $guruRole = Role::firstOrCreate(['role_name' => 'Guru']);
        $user = User::create([
            'username' => 'otp_web_user',
            'password' => Hash::make('oldpassword123'),
            'fullname' => 'OTP Web User',
            'phone_number' => '081234567801',
            'role_id' => $guruRole->id,
        ]);

        $this->withoutVite()->get('/login')->assertSee('Lupa Password via WhatsApp OTP?');
        $this->get('/forgot-password')->assertOk()->assertSee('WhatsApp OTP Security');
        $this->post('/forgot-password', ['identifier' => $user->username])
            ->assertRedirect('/reset-password')
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertNotNull($user->otp_code);
        $this->assertTrue($user->otp_expires_at->isFuture());

        $this->post('/reset-password', [
            'identifier' => $user->username,
            'otp_code' => '000000',
            'password' => 'newsecurepass123',
            'password_confirmation' => 'newsecurepass123',
        ])->assertSessionHasErrors('otp_code');

        $this->post('/reset-password', [
            'identifier' => $user->username,
            'otp_code' => $user->otp_code,
            'password' => 'newsecurepass123',
            'password_confirmation' => 'newsecurepass123',
        ])->assertRedirect('/login');
        $this->assertTrue(Hash::check('newsecurepass123', $user->fresh()->password));

        $apiUser = User::create([
            'username' => 'otp_api_user',
            'password' => Hash::make('oldpassword123'),
            'fullname' => 'OTP API User',
            'phone_number' => '081234567802',
            'role_id' => $guruRole->id,
        ]);

        $this->postJson('/api/forgot-password/send-otp', ['identifier' => $apiUser->username])
            ->assertOk();
        $apiUser->refresh();
        $this->postJson('/api/forgot-password/verify-otp', [
            'identifier' => $apiUser->username,
            'otp_code' => $apiUser->otp_code,
        ])->assertOk()->assertJson(['valid' => true]);
        $this->postJson('/api/forgot-password/reset', [
            'identifier' => $apiUser->username,
            'otp_code' => $apiUser->otp_code,
            'password' => 'newapipass123',
            'password_confirmation' => 'newapipass123',
        ])->assertOk();
        $this->assertTrue(Hash::check('newapipass123', $apiUser->fresh()->password));
    }

    // =========================================================================
    // 5. BACKEND STRICT ANTI-OVERLAP & SCHEDULING VALIDATION
    // =========================================================================
    public function test_booking_strictly_prevents_time_interval_overlap(): void
    {
        $guruRole = Role::firstOrCreate(['role_name' => 'Guru']);
        $picRole = Role::firstOrCreate(['role_name' => 'PIC']);

        $pic = User::create([
            'username'     => 'pic_room',
            'password'     => Hash::make('pass'),
            'fullname'     => 'PIC Room',
            'phone_number' => '081111111111',
            'role_id'      => $picRole->id,
        ]);

        $guru = User::create([
            'username'     => 'guru_booker',
            'password'     => Hash::make('pass'),
            'fullname'     => 'Guru Booker',
            'phone_number' => '082222222222',
            'role_id'      => $guruRole->id,
        ]);

        $room = Room::create([
            'room_name' => 'Ruang Sidang Utama',
            'capacity'  => 50,
            'location'  => 'Gedung Pusat',
            'pic_id'    => $pic->id,
            'is_active' => true,
        ]);

        $date = now()->addDays(5)->format('Y-m-d');

        // Existing approved booking: 09:00 - 11:00
        Event::create([
            'room_id'      => $room->id,
            'pic_id'       => $guru->id,
            'requester_id' => $guru->id,
            'event_name'   => 'Rapat Tahunan',
            'date'         => $date,
            'start_time'   => '09:00',
            'end_time'     => '11:00',
            'status'       => 'approved',
        ]);

        // Attempt 1: Overlaps inside (09:30 - 10:30) -> Should fail
        $this->actingAs($guru)->post('/events', [
            'room_id'    => $room->id,
            'event_name' => 'Bentrok di tengah',
            'date'       => $date,
            'start_time' => '09:30',
            'end_time'   => '10:30',
        ])->assertSessionHasErrors('time');

        // Attempt 2: Overlaps start (08:00 - 09:30) -> Should fail
        $this->actingAs($guru)->post('/events', [
            'room_id'    => $room->id,
            'event_name' => 'Bentrok awal',
            'date'       => $date,
            'start_time' => '08:00',
            'end_time'   => '09:30',
        ])->assertSessionHasErrors('time');

        // Attempt 3: Overlaps end (10:30 - 12:00) -> Should fail
        $this->actingAs($guru)->post('/events', [
            'room_id'    => $room->id,
            'event_name' => 'Bentrok akhir',
            'date'       => $date,
            'start_time' => '10:30',
            'end_time'   => '12:00',
        ])->assertSessionHasErrors('time');

        // Attempt 4: Adjacent before (08:00 - 09:00) -> Should SUCCEED
        $this->actingAs($guru)->post('/events', [
            'room_id'    => $room->id,
            'event_name' => 'Bersebelahan sebelum',
            'date'       => $date,
            'start_time' => '08:00',
            'end_time'   => '09:00',
        ])->assertRedirect()->assertSessionHas('success');

        // Attempt 5: Adjacent after (11:00 - 12:00) -> Should SUCCEED
        $this->actingAs($guru)->post('/events', [
            'room_id'    => $room->id,
            'event_name' => 'Bersebelahan sesudah',
            'date'       => $date,
            'start_time' => '11:00',
            'end_time'   => '12:00',
        ])->assertRedirect()->assertSessionHas('success');
    }

    public function test_decide_endpoint_prevents_approving_conflicting_events(): void
    {
        $adminRole = Role::firstOrCreate(['role_name' => 'Admin']);
        $guruRole = Role::firstOrCreate(['role_name' => 'Guru']);
        $picRole = Role::firstOrCreate(['role_name' => 'PIC']);

        $admin = User::create([
            'username'     => 'admin_decide',
            'password'     => Hash::make('pass'),
            'fullname'     => 'Admin Decide',
            'phone_number' => '081111111111',
            'role_id'      => $adminRole->id,
        ]);

        $guru = User::create([
            'username'     => 'guru_requester',
            'password'     => Hash::make('pass'),
            'fullname'     => 'Guru Requester',
            'phone_number' => '082222222222',
            'role_id'      => $guruRole->id,
        ]);

        $room = Room::create([
            'room_name' => 'Lab Fisika',
            'capacity'  => 30,
            'location'  => 'Gedung B',
            'pic_id'    => $admin->id,
            'is_active' => true,
        ]);

        $date = now()->addDays(3)->format('Y-m-d');

        // Approved Event: 13:00 - 15:00
        Event::create([
            'room_id'      => $room->id,
            'pic_id'       => $guru->id,
            'requester_id' => $guru->id,
            'event_name'   => 'Praktikum Fisika 1',
            'date'         => $date,
            'start_time'   => '13:00',
            'end_time'     => '15:00',
            'status'       => 'approved',
        ]);

        // Pending Event overlapping: 14:00 - 16:00
        $pendingEvent = Event::create([
            'room_id'      => $room->id,
            'pic_id'       => $guru->id,
            'requester_id' => $guru->id,
            'event_name'   => 'Praktikum Fisika 2 (Bentrok)',
            'date'         => $date,
            'start_time'   => '14:00',
            'end_time'     => '16:00',
            'status'       => 'pending',
        ]);

        $token = $admin->createToken('admin-test')->plainTextToken;

        // Approving pending event should be blocked due to conflict
        $this->patchJson('/api/events/' . $pendingEvent->id . '/decision', [
            'status' => 'approved',
        ], ['Authorization' => 'Bearer ' . $token])
             ->assertStatus(422);

        $this->assertSame('pending', $pendingEvent->fresh()->status);
    }

    // =========================================================================
    // 6. CALENDAR & ROUTING FIXES
    // =========================================================================
    public function test_calendar_displays_proper_month_stepper_and_authenticated_back_link(): void
    {
        $adminRole = Role::firstOrCreate(['role_name' => 'Admin']);
        $admin = User::create([
            'username'     => 'admin_cal',
            'password'     => Hash::make('pass'),
            'fullname'     => 'Admin Calendar',
            'phone_number' => '081234567890',
            'role_id'      => $adminRole->id,
        ]);

        // Logged-in admin opens calendar -> should link back to /admin/dashboard
        $this->actingAs($admin)->get('/calendar')
            ->assertOk()
            ->assertSee('/admin/dashboard')
            ->assertSee('month-stepper')
            ->assertSee('previous-month')
            ->assertSee('next-month')
            ->assertSee('month-title');

        // Guest opens calendar -> should link to public calendar /
        auth()->logout();
        $this->get('/calendar')
            ->assertOk()
            ->assertSee(route('public.calendar'));
    }
}
