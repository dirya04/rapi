<?php

namespace Tests\Feature\Api;

use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EventApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_calendar_is_readable_but_guest_cannot_submit_booking(): void
    {
        $pic = $this->makeUser('PIC');
        $room = Room::create([
            'room_name' => 'Ruang Kalender '.uniqid(),
            'capacity' => 30,
            'location' => 'Gedung Uji',
            'pic_id' => $pic->id,
            'is_active' => true,
        ]);
        $date = now()->addDays(2)->format('Y-m-d');

        $this->getJson('/api/calendar/events?from='.$date.'&to='.$date)
            ->assertOk()->assertJsonStructure(['data']);

        $this->postJson('/api/events', [
            'room_id' => $room->id,
            'event_name' => 'Pengajuan guest',
            'date' => $date,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ])->assertUnauthorized()->assertJsonStructure(['message']);
    }

    public function test_room_time_overlap_is_rejected_but_adjacent_booking_is_allowed(): void
    {
        $guru = $this->makeUser('Guru');
        $room = $this->makeRoom();
        $headers = $this->tokenHeaders($guru);
        $date = now()->addDays(5)->format('Y-m-d');

        $payload = [
            'room_id' => $room->id,
            'event_name' => 'Rapat pertama',
            'date' => $date,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ];

        $this->postJson('/api/events', $payload, $headers)
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $this->postJson('/api/events', [
            ...$payload,
            'event_name' => 'Bertabrakan',
            'start_time' => '10:30',
            'end_time' => '11:30',
        ], $headers)->assertUnprocessable()->assertJsonValidationErrors('time');

        $this->postJson('/api/events', [
            ...$payload,
            'event_name' => 'Tepat setelahnya',
            'start_time' => '11:00',
            'end_time' => '12:00',
        ], $headers)->assertCreated();

        $this->assertDatabaseHas('events', [
            'event_name' => 'Rapat pertama',
            'requester_id' => $guru->id,
            'pic_id' => $guru->id,
        ]);
    }

    public function test_admin_move_saves_reason_and_notifies_requester(): void
    {
        $guru = $this->makeUser('Guru');
        $admin = $this->makeUser('Admin');
        $oldRoom = $this->makeRoom('Ruang Lama');
        $newRoom = $this->makeRoom('Ruang Baru');
        $date = now()->addDays(7)->format('Y-m-d');

        $created = $this->postJson('/api/events', [
            'room_id' => $oldRoom->id,
            'event_name' => 'Agenda Pindah',
            'date' => $date,
            'start_time' => '09:00',
            'end_time' => '10:00',
        ], $this->tokenHeaders($guru))->assertCreated();

        $eventId = $created->json('data.id');

        $this->postJson('/api/events/'.$eventId.'/move', [
            'new_room_id' => $newRoom->id,
            'move_reason' => 'Ruang lama dipakai untuk kegiatan sekolah.',
        ], $this->tokenHeaders($admin))
            ->assertOk()
            ->assertJsonPath('data.room.id', $newRoom->id)
            ->assertJsonPath('data.move_reason', 'Ruang lama dipakai untuk kegiatan sekolah.');

        $this->assertDatabaseHas('events', [
            'id' => $eventId,
            'room_id' => $newRoom->id,
            'status' => 'moved',
            'move_reason' => 'Ruang lama dipakai untuk kegiatan sekolah.',
        ]);

        $notification = $guru->notifications()->firstOrFail();
        $this->assertSame($newRoom->room_name, $notification->data['new_room']);
        $this->assertSame('Ruang lama dipakai untuk kegiatan sekolah.', $notification->data['reason']);
    }

    public function test_public_calendar_only_returns_approved_and_moved_events(): void
    {
        $requester = $this->makeUser('Guru');
        $room = $this->makeRoom();
        $date = now()->addDays(3)->format('Y-m-d');

        foreach (['pending', 'approved', 'moved'] as $status) {
            Event::create([
                'room_id' => $room->id,
                'pic_id' => $requester->id,
                'requester_id' => $requester->id,
                'event_name' => ucfirst($status),
                'date' => $date,
                'start_time' => '13:00',
                'end_time' => '14:00',
                'status' => $status,
            ]);
        }
        Event::create([
            'room_id' => $room->id,
            'pic_id' => $requester->id,
            'requester_id' => $requester->id,
            'event_name' => 'Ditolak',
            'date' => $date,
            'start_time' => '14:00',
            'end_time' => '15:00',
            'status' => 'rejected',
        ]);

        $this->getJson('/api/calendar/events?from='.$date.'&to='.$date)
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['event_name' => 'Pending'])
            ->assertJsonMissing(['event_name' => 'Ditolak']);
    }

    public function test_move_rejects_conflicting_target_and_non_admin(): void
    {
        $guru = $this->makeUser('Guru');
        $admin = $this->makeUser('Admin');
        $sourceRoom = $this->makeRoom('Ruang Sumber');
        $targetRoom = $this->makeRoom('Ruang Tujuan');
        $date = now()->addDays(8)->format('Y-m-d');

        $source = $this->postJson('/api/events', [
            'room_id' => $sourceRoom->id,
            'event_name' => 'Agenda yang dipindah',
            'date' => $date,
            'start_time' => '09:00',
            'end_time' => '10:00',
        ], $this->tokenHeaders($guru))->assertCreated();

        $this->postJson('/api/events', [
            'room_id' => $targetRoom->id,
            'event_name' => 'Agenda ruang tujuan',
            'date' => $date,
            'start_time' => '09:30',
            'end_time' => '10:30',
        ], $this->tokenHeaders($guru))->assertCreated();

        $movePayload = [
            'new_room_id' => $targetRoom->id,
            'move_reason' => 'Penjadwalan ulang',
        ];
        $moveUrl = '/api/events/'.$source->json('data.id').'/move';

        $this->postJson($moveUrl, $movePayload, $this->tokenHeaders($guru))->assertForbidden();
        $this->postJson($moveUrl, $movePayload, $this->tokenHeaders($admin))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('new_room_id');

        $this->assertDatabaseHas('events', [
            'id' => $source->json('data.id'),
            'room_id' => $sourceRoom->id,
            'status' => 'pending',
        ]);
    }

    private function makeUser(string $roleName): User
    {
        $role = Role::firstOrCreate(['role_name' => $roleName]);

        return User::create([
            'username' => strtolower($roleName).'_'.uniqid(),
            'password' => Hash::make('password123'),
            'fullname' => $roleName.' API Tester',
            'phone_number' => '081234567890',
            'role_id' => $role->id,
        ]);
    }

    private function makeRoom(string $name = 'Ruang Uji'): Room
    {
        $pic = $this->makeUser('PIC');

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
        $token = $user->createToken('feature-test')->plainTextToken;

        return ['Authorization' => 'Bearer '.$token];
    }
}
