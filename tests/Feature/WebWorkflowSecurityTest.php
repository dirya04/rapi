<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebWorkflowSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_and_api_booking_share_conflict_rules_and_web_routes_enforce_roles(): void
    {
        $guru = $this->makeUser('Guru');
        $pic = $this->makeUser('PIC');
        $room = $this->makeRoom($pic);
        $date = now()->addDays(5)->format('Y-m-d');
        $webBooking = [
            'room_id' => $room->id,
            'event_name' => 'Agenda dari web',
            'date' => $date,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ];

        $this->post('/events', $webBooking)->assertRedirect('/login');
        $this->actingAs($pic)->post('/events', [
            ...$webBooking,
            'event_name' => 'Agenda PIC',
            'start_time' => '08:00',
            'end_time' => '09:00',
        ])->assertRedirect('/pic/dashboard');
        $this->actingAs($guru)->post('/events', $webBooking)
            ->assertRedirect('/guru/dashboard');

        $this->postJson('/api/events', [
            ...$webBooking,
            'event_name' => 'Bentrok lewat API',
            'start_time' => '10:30',
            'end_time' => '11:30',
        ], $this->tokenHeaders($guru))->assertUnprocessable()->assertJsonValidationErrors('time');

        $apiBooking = [
            ...$webBooking,
            'event_name' => 'Agenda dari API',
            'start_time' => '13:00',
            'end_time' => '14:00',
        ];
        $this->postJson('/api/events', $apiBooking, $this->tokenHeaders($guru))->assertCreated();

        $this->from('/admin/dashboard')->actingAs($guru)->post('/events', [
            ...$apiBooking,
            'event_name' => 'Bentrok lewat web',
            'start_time' => '13:30',
            'end_time' => '14:30',
        ])->assertRedirect('/admin/dashboard')->assertSessionHasErrors('time');
    }

    public function test_web_booking_conflicts_redirect_with_a_clear_validation_error_for_admin_and_pic(): void
    {
        $admin = $this->makeUser('Admin');
        $pic = $this->makeUser('PIC');
        $requester = $this->makeUser('Guru');
        $room = $this->makeRoom($pic);
        $date = now()->addDays(5)->format('Y-m-d');

        Event::create([
            'room_id' => $room->id,
            'pic_id' => $requester->id,
            'requester_id' => $requester->id,
            'event_name' => 'Agenda yang sudah terdaftar',
            'date' => $date,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => 'approved',
        ]);

        $booking = [
            'room_id' => $room->id,
            'event_name' => 'Agenda bertabrakan',
            'date' => $date,
            'start_time' => '10:30',
            'end_time' => '11:30',
        ];
        $errorMessage = 'Sorry, this room and time slot are already booked or conflict with another agenda.';

        $this->from('/admin/dashboard')->actingAs($admin)->post('/events', $booking)
            ->assertRedirect('/admin/dashboard')
            ->assertSessionHasErrors(['time' => $errorMessage])
            ->assertSessionHas('_old_input.event_name', 'Agenda bertabrakan');

        $this->from('/pic/dashboard')->actingAs($pic)->post('/events', $booking)
            ->assertRedirect('/pic/dashboard')
            ->assertSessionHasErrors(['time' => $errorMessage])
            ->assertSessionHas('_old_input.event_name', 'Agenda bertabrakan');
    }

    public function test_web_and_api_complaint_updates_are_limited_to_assigned_pic(): void
    {
        $reporter = $this->makeUser('Guru');
        $assignedPic = $this->makeUser('PIC');
        $otherPic = $this->makeUser('PIC');
        $room = $this->makeRoom($assignedPic);
        $complaint = Complaint::create([
            'room_id' => $room->id,
            'reporter_id' => $reporter->id,
            'description' => 'Keluhan uji web dan API',
            'photo_path' => 'complaints/test.jpg',
            'status_repair' => 'Belum Diperbaiki',
            'reported_at' => now(),
        ]);
        $payload = [
            'status_repair' => 'Menunggu Spare Part',
            'progress_note' => 'Suku cadang sudah dipesan.',
        ];

        $this->actingAs($otherPic)->post('/complaints/'.$complaint->id.'/status', $payload)
            ->assertForbidden();
        $this->patchJson('/api/complaints/'.$complaint->id.'/status', $payload, $this->tokenHeaders($otherPic))
            ->assertForbidden();
        $this->actingAs($reporter)->post('/complaints/'.$complaint->id.'/status', $payload)
            ->assertForbidden();

        $this->actingAs($assignedPic)->post('/complaints/'.$complaint->id.'/status', $payload)
            ->assertRedirect('/pic/dashboard');
        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'handler_id' => $assignedPic->id,
            'status_repair' => 'Menunggu Spare Part',
            'progress_note' => 'Suku cadang sudah dipesan.',
        ]);
    }

    public function test_pic_dashboard_only_loads_delegated_rooms_events_and_complaints(): void
    {
        $pic = $this->makeUser('PIC');
        $otherPic = $this->makeUser('PIC');
        $reporter = $this->makeUser('Guru');
        $assignedRoom = $this->makeRoom($pic, 'Assigned');
        $otherRoom = $this->makeRoom($otherPic, 'Other');
        $assignedComplaint = Complaint::create([
            'room_id' => $assignedRoom->id,
            'reporter_id' => $reporter->id,
            'description' => 'Assigned complaint',
            'photo_path' => 'complaints/assigned.jpg',
            'status_repair' => 'Menunggu Spare Part',
            'reported_at' => now(),
        ]);
        $otherComplaint = Complaint::create([
            'room_id' => $otherRoom->id,
            'reporter_id' => $reporter->id,
            'description' => 'Other complaint',
            'photo_path' => 'complaints/other.jpg',
            'status_repair' => 'Belum Diperbaiki',
            'reported_at' => now(),
        ]);
        $assignedEvent = Event::create([
            'room_id' => $assignedRoom->id,
            'pic_id' => $reporter->id,
            'requester_id' => $reporter->id,
            'event_name' => 'Assigned event',
            'date' => now()->addDays(3)->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => 'approved',
        ]);
        Event::create([
            'room_id' => $otherRoom->id,
            'pic_id' => $reporter->id,
            'requester_id' => $reporter->id,
            'event_name' => 'Other event',
            'date' => now()->addDays(3)->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'status' => 'approved',
        ]);

        $response = $this->withoutVite()->actingAs($pic)->get('/pic/dashboard')->assertOk()
            ->assertViewHas('assignedRooms', fn ($rooms) => $rooms->pluck('id')->all() === [$assignedRoom->id])
            ->assertViewHas('bookingRooms', fn ($rooms) => $rooms->pluck('id')->sort()->values()->all() === [$assignedRoom->id, $otherRoom->id])
            ->assertViewHas('complaints', fn ($complaints) => $complaints->pluck('id')->all() === [$assignedComplaint->id])
            ->assertViewHas('events', fn ($events) => $events->pluck('id')->all() === [$assignedEvent->id])
            ->assertSee('Menunggu Spare Part')
            ->assertSee('Simpan status')
            ->assertSee('name="status_repair"', false)
            ->assertSee('name="progress_note"', false)
            ->assertDontSee('Other complaint')
            ->assertDontSee('Other event');

        $this->assertNotNull($response);
        $this->assertNotSame($assignedComplaint->id, $otherComplaint->id);

        $this->actingAs($pic)->post('/events', [
            'room_id' => $otherRoom->id,
            'event_name' => 'PIC books another room',
            'date' => now()->addDays(5)->toDateString(),
            'start_time' => '13:00',
            'end_time' => '14:00',
        ])->assertRedirect('/pic/dashboard')->assertSessionHas('success');
    }

    public function test_guru_dashboard_only_shows_reporters_complaints(): void
    {
        $guru = $this->makeUser('Guru');
        $otherGuru = $this->makeUser('Organisasi');
        $pic = $this->makeUser('PIC');
        $room = $this->makeRoom($pic);
        Complaint::create([
            'room_id' => $room->id,
            'reporter_id' => $guru->id,
            'description' => 'My complaint description',
            'photo_path' => 'complaints/mine.jpg',
            'status_repair' => 'Sudah Diperbaiki',
            'reported_at' => now(),
        ]);
        Complaint::create([
            'room_id' => $room->id,
            'reporter_id' => $otherGuru->id,
            'description' => 'Other reporter secret description',
            'photo_path' => 'complaints/other.jpg',
            'status_repair' => 'Belum Diperbaiki',
            'reported_at' => now(),
        ]);

        $this->actingAs($guru)->get('/guru/dashboard')->assertOk()
            ->assertSee('My complaint description')
            ->assertSee('Sudah Diperbaiki')
            ->assertDontSee('Other reporter secret description');
    }

    public function test_public_home_and_full_calendar_only_expose_approved_events(): void
    {
        $requester = $this->makeUser('Guru');
        $room = $this->makeRoom($this->makeUser('PIC'));
        foreach (['approved', 'moved', 'pending', 'rejected'] as $index => $status) {
            Event::create([
                'room_id' => $room->id,
                'pic_id' => $requester->id,
                'requester_id' => $requester->id,
                'event_name' => 'Public check '.$status,
                'date' => today()->toDateString(),
                'start_time' => sprintf('%02d:00', 9 + $index),
                'end_time' => sprintf('%02d:30', 9 + $index),
                'status' => $status,
            ]);
        }

        $this->get('/')->assertOk()
            ->assertSee('Public check approved')
            ->assertSee('Public check moved')
            ->assertDontSee('Public check pending')
            ->assertDontSee('Public check rejected')
            ->assertSee(route('calendar.index'));

        $this->get('/calendar')->assertOk()
            ->assertSee('/api/calendar/events?from=', false)
            ->assertSee('next-month')
            ->assertSee('previous-month');
    }

    public function test_waka_login_redirects_to_admin_dashboard(): void
    {
        $waka = $this->makeUser('Waka');

        $this->post('/login', [
            'username' => $waka->username,
            'password' => 'password123',
        ])->assertRedirect('/admin/dashboard');
    }

    public function test_database_seeder_provisions_a_waka_account_and_delegated_rooms(): void
    {
        $this->seed();

        $waka = User::where('username', 'waka')->with('role')->firstOrFail();
        $this->assertSame('Waka', $waka->role->role_name);
        $this->assertDatabaseHas('rooms', ['room_type' => 'kelas']);
        $this->assertDatabaseHas('rooms', ['pic_id' => User::where('username', 'sarpras')->value('id')]);
    }

    public function test_waka_can_submit_web_bookings_and_complaints(): void
    {
        Storage::fake('public');
        $waka = $this->makeUser('Waka');
        $pic = $this->makeUser('PIC');
        $room = $this->makeRoom($pic, 'Ruang Waka');

        $this->actingAs($waka)->post('/events', [
            'room_id' => $room->id,
            'event_name' => 'Agenda Waka',
            'date' => now()->addDays(5)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
        ])->assertRedirect('/admin/dashboard')->assertSessionHas('success');

        $this->actingAs($waka)->post('/complaints', [
            'room_id' => $room->id,
            'description' => 'Laporan Waka',
            'photo' => UploadedFile::fake()->image('waka.jpg'),
        ])->assertRedirect('/admin/dashboard')->assertSessionHas('success');

        $this->assertDatabaseHas('events', [
            'event_name' => 'Agenda Waka',
            'requester_id' => $waka->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('complaints', [
            'reporter_id' => $waka->id,
            'description' => 'Laporan Waka',
        ]);
    }

    public function test_print_report_is_scoped_to_the_current_role(): void
    {
        $guru = $this->makeUser('Guru');
        $otherGuru = $this->makeUser('Organisasi');
        $pic = $this->makeUser('PIC');
        $room = $this->makeRoom($pic);

        Complaint::create([
            'room_id' => $room->id,
            'reporter_id' => $guru->id,
            'description' => 'Private reporter complaint',
            'photo_path' => 'complaints/private.jpg',
            'status_repair' => 'Belum Diperbaiki',
            'reported_at' => now(),
        ]);
        Complaint::create([
            'room_id' => $room->id,
            'reporter_id' => $otherGuru->id,
            'description' => 'Other reporter complaint',
            'photo_path' => 'complaints/other.jpg',
            'status_repair' => 'Belum Diperbaiki',
            'reported_at' => now(),
        ]);

        $this->actingAs($guru)->get('/report/print')->assertOk()
            ->assertSee('Private reporter complaint')
            ->assertDontSee('Other reporter complaint');
    }

    public function test_waka_can_open_admin_dashboard_and_trigger_whatsapp_escalation(): void
    {
        $waka = $this->makeUser('Waka');
        $pic = $this->makeUser('PIC');
        $reporter = $this->makeUser('Guru');
        $room = $this->makeRoom($pic, 'Ruang Eskalasi');
        $complaint = Complaint::create([
            'room_id' => $room->id,
            'reporter_id' => $reporter->id,
            'description' => 'Proyektor perlu diperiksa',
            'photo_path' => 'complaints/escalation.jpg',
            'status_repair' => 'Belum Diperbaiki',
            'reported_at' => now()->subHours(25),
        ]);

        $this->actingAs($waka)->get('/admin/dashboard')->assertOk()
            ->assertSee('Kirim Peringatan WA')
            ->assertSee(route('complaints.whatsapp', $complaint->id));
        $this->actingAs($waka)->get(route('complaints.whatsapp', $complaint->id))
            ->assertRedirect('https://wa.me/6281234567890?text='.rawurlencode(
                "Peringatan RAPI-Sekolah: pengaduan kerusakan perlu ditindaklanjuti.\n".
                'Nomor laporan: #'.$complaint->id."\n".
                'Ruang: '.$room->room_name."\n".
                'Waktu laporan: '.$complaint->reported_at->format('d-m-Y H:i')."\n".
                "Status: Belum Diperbaiki\n".
                'Detail: Proyektor perlu diperiksa'
            ));

        $targetRoom = $this->makeRoom($pic, 'Ruang Tujuan Waka');
        $event = Event::create([
            'room_id' => $room->id,
            'pic_id' => $reporter->id,
            'requester_id' => $reporter->id,
            'event_name' => 'Agenda untuk Waka',
            'date' => now()->addDays(4)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'pending',
        ]);
        $this->postJson('/api/events/'.$event->id.'/move', [
            'new_room_id' => $targetRoom->id,
            'move_reason' => 'Override oleh Waka',
        ], $this->tokenHeaders($waka))->assertOk();
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'room_id' => $targetRoom->id,
            'move_reason' => 'Override oleh Waka',
        ]);
    }

    public function test_web_and_api_complaint_creation_require_photo_and_active_room(): void
    {
        Storage::fake('public');
        $reporter = $this->makeUser('Guru');
        $pic = $this->makeUser('PIC');
        $activeRoom = $this->makeRoom($pic, 'Ruang Aktif');
        $inactiveRoom = Room::create([
            'room_name' => 'Ruang Nonaktif '.uniqid(),
            'capacity' => 30,
            'location' => 'Gedung Uji',
            'pic_id' => $pic->id,
            'is_active' => false,
        ]);
        $missingPhoto = ['room_id' => $activeRoom->id, 'description' => 'Tanpa foto'];

        $this->from('/admin/dashboard')->actingAs($reporter)->post('/complaints', $missingPhoto)
            ->assertRedirect('/admin/dashboard')->assertSessionHasErrors('photo');
        $this->postJson('/api/complaints', $missingPhoto, $this->tokenHeaders($reporter))
            ->assertUnprocessable()->assertJsonValidationErrors('photo');

        $inactivePhoto = [
            'room_id' => $inactiveRoom->id,
            'description' => 'Ruang nonaktif',
            'photo' => UploadedFile::fake()->image('inactive.jpg'),
        ];
        $this->from('/admin/dashboard')->actingAs($reporter)->post('/complaints', $inactivePhoto)
            ->assertRedirect('/admin/dashboard')->assertSessionHasErrors('room_id');
        $this->postJson('/api/complaints', $inactivePhoto, $this->tokenHeaders($reporter))
            ->assertUnprocessable();

        $this->actingAs($reporter)->post('/complaints', [
            'room_id' => $activeRoom->id,
            'description' => 'Bukti kerusakan web',
            'photo' => UploadedFile::fake()->image('web.jpg'),
        ])->assertRedirect('/guru/dashboard');
        $this->assertDatabaseHas('complaints', [
            'room_id' => $activeRoom->id,
            'reporter_id' => $reporter->id,
            'description' => 'Bukti kerusakan web',
        ]);

        $apiResponse = $this->postJson('/api/complaints', [
            'room_id' => $activeRoom->id,
            'description' => 'Bukti kerusakan API',
            'photo' => UploadedFile::fake()->image('api.jpg'),
        ], $this->tokenHeaders($reporter))->assertCreated();
        Storage::disk('public')->assertExists($apiResponse->json('data.photo_path'));

        $this->postJson('/api/complaints', [
            'room_id' => $activeRoom->id,
            'description' => 'Foto API tepat batas 10 MB',
            'photo' => UploadedFile::fake()->image('api-max-size.jpg')->size(10240),
        ], $this->tokenHeaders($reporter))->assertCreated();

        $this->postJson('/api/complaints', [
            'room_id' => $activeRoom->id,
            'description' => 'Foto API di atas batas 10 MB',
            'photo' => UploadedFile::fake()->image('api-too-large.jpg')->size(10241),
        ], $this->tokenHeaders($reporter))->assertUnprocessable()
            ->assertJsonValidationErrors('photo');
    }

    public function test_only_admin_can_move_agenda_through_web_route(): void
    {
        $guru = $this->makeUser('Guru');
        $pic = $this->makeUser('PIC');
        $source = $this->makeRoom($pic, 'Ruang Asal');
        $target = $this->makeRoom($pic, 'Ruang Tujuan');
        $event = Event::create([
            'room_id' => $source->id,
            'pic_id' => $guru->id,
            'requester_id' => $guru->id,
            'event_name' => 'Agenda untuk uji akses',
            'date' => now()->addDays(7)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'pending',
        ]);

        $this->actingAs($guru)->post('/events/'.$event->id.'/move', [
            'new_room_id' => $target->id,
            'move_reason' => 'Uji pembatasan role',
        ])->assertForbidden();
    }

    public function test_web_complaint_photo_upload_is_stored_with_a_unique_public_filename(): void
    {
        Storage::fake('public');
        $reporter = $this->makeUser('Guru');
        $pic = $this->makeUser('PIC');
        $room = $this->makeRoom($pic);

        foreach (['first.jpg', 'second.png'] as $filename) {
            $this->actingAs($reporter)->post('/complaints', [
                'room_id' => $room->id,
                'description' => 'Tes unggah foto '.$filename,
                'photo' => UploadedFile::fake()->image($filename),
            ])->assertRedirect('/guru/dashboard')->assertSessionHas('success');
        }

        $photos = DB::table('complaints')->orderBy('id')->pluck('photo_path');
        $this->assertCount(2, $photos);
        $this->assertNotSame($photos[0], $photos[1]);

        foreach ($photos as $photoPath) {
            $this->assertStringStartsWith('complaints/', $photoPath);
            $this->assertMatchesRegularExpression('/^complaints\/[0-9a-f-]+\.(jpg|png)$/', $photoPath);
            Storage::disk('public')->assertExists($photoPath);
        }

        $this->actingAs($reporter)->post('/complaints', [
            'room_id' => $room->id,
            'description' => 'Foto tepat batas maksimum',
            'photo' => UploadedFile::fake()->image('max-size.jpg')->size(10240),
        ])->assertRedirect('/guru/dashboard')->assertSessionHas('success');

        $maxSizePhoto = DB::table('complaints')->orderByDesc('id')->value('photo_path');
        Storage::disk('public')->assertExists($maxSizePhoto);

        $this->from('/guru/dashboard')->actingAs($reporter)->post('/complaints', [
            'room_id' => $room->id,
            'description' => 'Foto terlalu besar',
            'photo' => UploadedFile::fake()->image('too-large.jpg')->size(10241),
        ])->assertRedirect('/guru/dashboard')->assertSessionHasErrors('photo');
    }

    public function test_profile_is_available_to_authenticated_users_and_updates_account_safely(): void
    {
        $user = $this->makeUser('Guru');
        $originalPassword = $user->password;

        $this->get('/profile')->assertRedirect('/login');

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('Nama lengkap')
            ->assertSee('Nomor WhatsApp')
            ->assertDontSee('Ubah kata sandi')
            ->assertDontSee('name="password"', false)
            ->assertDontSee('name="current_password"', false);

        $this->actingAs($user)->put('/profile', [
            'fullname' => 'Nama Profil Baru',
            'username' => $user->username,
            'phone_number' => '081298765432',
        ])->assertRedirect('/profile')->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Nama Profil Baru', $user->fullname);
        $this->assertSame('081298765432', $user->phone_number);
        $this->assertSame($originalPassword, $user->password);

        $this->actingAs($user)->put('/profile', [
            'fullname' => 'Nama Profil Baru',
            'username' => $user->username,
            'phone_number' => $user->phone_number,
            'current_password' => 'password123',
            'password' => 'newSecurePass123',
            'password_confirmation' => 'newSecurePass123',
        ])->assertRedirect('/profile')->assertSessionHas('success');

        $this->assertSame($originalPassword, $user->fresh()->password);

        foreach (['PIC', 'Organisasi'] as $roleName) {
            $otherUser = $this->makeUser($roleName);
            $password = $otherUser->password;

            $this->actingAs($otherUser)->put('/profile', [
                'fullname' => $otherUser->fullname,
                'username' => $otherUser->username,
                'phone_number' => $otherUser->phone_number,
                'password' => 'profilePasswordChange123',
                'password_confirmation' => 'profilePasswordChange123',
            ])->assertRedirect('/profile')->assertSessionHas('success');

            $this->assertSame($password, $otherUser->fresh()->password);
        }
    }

    public function test_dashboard_and_public_agenda_share_today_approved_bookings_while_master_lists_include_pending(): void
    {
        $admin = $this->makeUser('Admin');
        $pic = $this->makeUser('PIC');
        $guru = $this->makeUser('Guru');
        $room = $this->makeRoom($pic);

        foreach ([
            ['Today approved', today(), 'approved'],
            ['Today moved', today(), 'moved'],
            ['Today pending', today(), 'pending'],
            ['Today rejected', today(), 'rejected'],
            ['Future approved', today()->addDay(), 'approved'],
        ] as [$name, $date, $status]) {
            Event::create([
                'room_id' => $room->id,
                'pic_id' => $guru->id,
                'requester_id' => $guru->id,
                'event_name' => $name,
                'date' => $date->toDateString(),
                'start_time' => '09:00',
                'end_time' => '10:00',
                'status' => $status,
            ]);
        }

        $this->withoutVite()->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertViewHas('events', fn ($events) => $events->pluck('event_name')->all() === [
                'Today approved',
                'Today moved',
                'Today pending',
                'Future approved',
            ])
            ->assertViewHas('todayEvents', fn ($events) => $events->pluck('event_name')->all() === [
                'Today approved',
                'Today moved',
            ])
            ->assertSee('Daftar Agenda Hari Ini')
            ->assertSee('Today approved')
            ->assertDontSee('Today rejected');

        $this->get('/')->assertOk()
            ->assertSee('Today approved')
            ->assertSee('Today moved')
            ->assertDontSee('Today pending')
            ->assertDontSee('Today rejected');

        $this->withoutVite()->actingAs($pic)->get('/pic/dashboard')
            ->assertOk()
            ->assertViewHas('todayEvents', fn ($events) => $events->pluck('event_name')->all() === [
                'Today approved',
                'Today moved',
            ]);

        $this->withoutVite()->actingAs($guru)->get('/guru/dashboard')
            ->assertOk()
            ->assertViewHas('todayEvents', fn ($events) => $events->pluck('event_name')->all() === [
                'Today approved',
                'Today moved',
            ]);
    }

    public function test_all_web_roles_can_submit_bookings_and_complaints_and_see_pending_agendas(): void
    {
        Storage::fake('public');
        $pic = $this->makeUser('PIC');
        $room = $this->makeRoom($pic);

        foreach (['Admin', 'Waka', 'PIC', 'Guru', 'Organisasi'] as $index => $roleName) {
            $user = $this->makeUser($roleName);
            $startHour = 8 + $index;
            $bookingResponse = $this->actingAs($user)->post('/events', [
                'room_id' => $room->id,
                'event_name' => 'Agenda '.$roleName,
                'date' => today()->toDateString(),
                'start_time' => sprintf('%02d:00', $startHour),
                'end_time' => sprintf('%02d:30', $startHour),
            ])->assertRedirect()->assertSessionHas('success')->assertSessionHas('created_event_id');

            $eventId = session('created_event_id');
            $this->assertDatabaseHas('events', [
                'id' => $eventId,
                'requester_id' => $user->id,
                'status' => 'pending',
            ]);

            $dashboardPath = match ($roleName) {
                'Admin', 'Waka' => '/admin/dashboard',
                'PIC' => '/pic/dashboard',
                default => '/guru/dashboard',
            };
            $this->withoutVite()->actingAs($user)->get($dashboardPath)
                ->assertOk()
                ->assertSee('Agenda '.$roleName)
                ->assertSee('Pending')
                ->assertSee(route('profile.edit'), false);

            $this->actingAs($user)->post('/complaints', [
                'room_id' => $room->id,
                'description' => 'Laporan '.$roleName,
                'photo' => UploadedFile::fake()->image(strtolower($roleName).'.jpg'),
            ])->assertRedirect($dashboardPath)->assertSessionHas('success');

            $this->assertDatabaseHas('complaints', [
                'reporter_id' => $user->id,
                'description' => 'Laporan '.$roleName,
            ]);
        }
    }

    public function test_laboratory_booking_requires_admin_and_assigned_pic_approval(): void
    {
        $admin = $this->makeUser('Admin');
        $labPic = $this->makeUser('PIC');
        $requester = $this->makeUser('Guru');
        $labRoom = $this->makeRoom($labPic, 'Lab Workflow');
        $labRoom->update(['room_type' => 'laboratorium']);
        $event = Event::create([
            'room_id' => $labRoom->id,
            'pic_id' => $requester->id,
            'requester_id' => $requester->id,
            'event_name' => 'Dual approval lab event',
            'date' => today()->addDays(2)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'pending',
        ]);

        $this->actingAs($labPic)->post('/events/'.$event->id.'/decision', [
            'status' => 'approved',
        ])->assertForbidden();
        $event->refresh();
        $this->assertSame('pending', $event->status);
        $this->assertNull($event->approved_by);

        $this->actingAs($admin)->post('/events/'.$event->id.'/decision', [
            'status' => 'approved',
        ])->assertRedirect('/admin/dashboard')->assertSessionHas('success');
        $event->refresh();
        $this->assertSame('approved', $event->status);
        $this->assertSame($admin->id, $event->approved_by);

        $anotherPic = $this->makeUser('PIC');
        $otherEvent = Event::create([
            'room_id' => $labRoom->id,
            'pic_id' => $requester->id,
            'requester_id' => $requester->id,
            'event_name' => 'PIC authorization check',
            'date' => today()->addDays(3)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'pending',
        ]);
        $this->actingAs($anotherPic)->post('/events/'.$otherEvent->id.'/decision', [
            'status' => 'approved',
        ])->assertForbidden();

        $regularRoom = $this->makeRoom($labPic, 'Regular Workflow');
        $regularEvent = Event::create([
            'room_id' => $regularRoom->id,
            'pic_id' => $requester->id,
            'requester_id' => $requester->id,
            'event_name' => 'Admin-only approval event',
            'date' => today()->addDays(4)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'pending',
        ]);
        $this->actingAs($labPic)->post('/events/'.$regularEvent->id.'/decision', [
            'status' => 'approved',
        ])->assertForbidden();
        $this->actingAs($admin)->post('/events/'.$regularEvent->id.'/decision', [
            'status' => 'approved',
        ])->assertRedirect('/admin/dashboard');
        $this->assertSame('approved', $regularEvent->fresh()->status);
    }

    public function test_only_admin_can_reset_or_update_another_users_password(): void
    {
        $admin = $this->makeUser('Admin');
        $waka = $this->makeUser('Waka');
        $target = $this->makeUser('Guru');
        $originalPassword = $target->password;

        $this->actingAs($waka)->post('/admin/users/'.$target->id.'/reset-password', [
            'password' => 'newWakaPassword123',
        ])->assertForbidden();
        $this->withoutVite()->actingAs($waka)->get('/admin/dashboard')
            ->assertOk()
            ->assertDontSee('Reset Sandi');

        $this->actingAs($waka)->putJson('/api/admin/users/'.$target->id, [
            'password' => 'newWakaPassword123',
        ], $this->tokenHeaders($waka))->assertForbidden();
        $this->assertSame($originalPassword, $target->fresh()->password);

        $this->actingAs($admin)->putJson('/api/admin/users/'.$target->id, [
            'password' => 'newAdminPassword123',
        ], $this->tokenHeaders($admin))->assertOk();
        $this->assertTrue(Hash::check('newAdminPassword123', $target->fresh()->password));
        $this->withoutVite()->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Reset Sandi');
    }

    private function makeUser(string $roleName): User
    {
        $role = Role::firstOrCreate(['role_name' => $roleName]);

        return User::create([
            'username' => strtolower($roleName).'_'.uniqid(),
            'password' => Hash::make('password123'),
            'fullname' => $roleName.' Web Tester',
            'phone_number' => '081234567890',
            'role_id' => $role->id,
        ]);
    }

    private function makeRoom(User $pic, string $name = 'Ruang Uji'): Room
    {
        return Room::create([
            'room_name' => $name.' '.uniqid(),
            'capacity' => 30,
            'location' => 'Gedung Uji',
            'pic_id' => $pic->id,
            'is_active' => true,
        ]);
    }

    private function tokenHeaders(User $user): array
    {
        Auth::forgetGuards();

        return ['Authorization' => 'Bearer '.$user->createToken('web-security-test')->plainTextToken];
    }
}