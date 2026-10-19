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
        $this->actingAs($pic)->post('/events', $webBooking)->assertForbidden();
        $this->actingAs($guru)->post('/events', $webBooking)
            ->assertRedirect('/admin/dashboard');

        $this->postJson('/api/events', [
            ...$webBooking,
            'event_name' => 'Bentrok lewat API',
            'start_time' => '10:30',
            'end_time' => '11:30',
        ], $this->tokenHeaders($guru))->assertUnprocessable()->assertJsonValidationErrors('room_id');

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
        ])->assertRedirect('/admin/dashboard')->assertSessionHasErrors('room_id');
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
        $payload = ['status_repair' => 'Menunggu Spare Part'];

        $this->actingAs($otherPic)->post('/complaints/'.$complaint->id.'/status', $payload)
            ->assertForbidden();
        $this->patchJson('/api/complaints/'.$complaint->id.'/status', $payload, $this->tokenHeaders($otherPic))
            ->assertForbidden();
        $this->actingAs($reporter)->post('/complaints/'.$complaint->id.'/status', $payload)
            ->assertForbidden();

        $this->actingAs($assignedPic)->post('/complaints/'.$complaint->id.'/status', $payload)
            ->assertRedirect('/admin/dashboard');
        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'handler_id' => $assignedPic->id,
            'status_repair' => 'Menunggu Spare Part',
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
        ])->assertRedirect('/admin/dashboard');
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