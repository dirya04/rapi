<?php

namespace Tests\Feature\Api;

use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RapiApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_rejected_events_leave_the_slot_available(): void
    {
        $requester = $this->makeUser('Guru');
        $room = $this->makeRoom($this->makeUser('PIC'));
        $date = now()->addDay()->format('Y-m-d');
        $headers = $this->headersFor($requester);

        $this->makeEvent($requester, $room, $date, '08:00', '09:00', 'rejected');
        $this->postJson('/api/events', $this->booking($room, $date, '08:30', '09:30'), $headers)
            ->assertCreated();

        foreach ([
            'pending' => '10:00',
            'approved' => '12:00',
            'moved' => '14:00',
        ] as $status => $startTime) {
            $endTime = date('H:i', strtotime($startTime.' +1 hour'));
            $this->makeEvent($requester, $room, $date, $startTime, $endTime, $status);

            $conflictingStart = date('H:i', strtotime($startTime.' +30 minutes'));
            $conflictingEnd = date('H:i', strtotime($endTime.' +30 minutes'));
            $this->postJson('/api/events', $this->booking($room, $date, $conflictingStart, $conflictingEnd), $headers)
                ->assertUnprocessable()->assertJsonValidationErrors('room_id');
        }
    }

    public function test_classrooms_are_bookable_only_on_weekends_through_api_and_web(): void
    {
        $guru = $this->makeUser('Guru');
        $classroom = $this->makeRoom($this->makeUser('PIC'), 'kelas');
        $monday = today()->next(Carbon::MONDAY)->toDateString();
        $saturday = today()->next(Carbon::SATURDAY)->toDateString();
        $sunday = today()->next(Carbon::SUNDAY)->toDateString();

        $this->postJson('/api/events', $this->booking($classroom, $monday, '09:00', '10:00'), $this->headersFor($guru))
            ->assertUnprocessable()->assertJsonValidationErrors('date');
        $this->postJson('/api/events', $this->booking($classroom, $saturday, '09:00', '10:00'), $this->headersFor($guru))
            ->assertCreated();

        $this->from('/admin/dashboard')->actingAs($guru)->post('/events', [
            ...$this->booking($classroom, $monday, '11:00', '12:00'),
        ])->assertRedirect('/admin/dashboard')->assertSessionHasErrors('date');
        $this->actingAs($guru)->post('/events', $this->booking($classroom, $sunday, '11:00', '12:00'))
            ->assertRedirect('/admin/dashboard')->assertSessionHas('success');
    }

    public function test_tv_display_returns_only_todays_active_and_upcoming_agendas(): void
    {
        $this->travelTo(today()->setTime(8, 0));
        $requester = $this->makeUser('Guru');
        $activeRoom = $this->makeRoom($this->makeUser('PIC'));
        $inactiveRoom = Room::create([
            'room_name' => 'Ruang Nonaktif '.uniqid(),
            'room_type' => 'lainnya',
            'capacity' => 20,
            'location' => 'Gedung Uji',
            'pic_id' => $this->makeUser('PIC')->id,
            'is_active' => false,
        ]);
        $date = today()->toDateString();
        $this->makeEvent($requester, $activeRoom, $date, '09:00', '10:00', 'approved');
        $this->makeEvent($requester, $activeRoom, $date, '11:00', '12:00', 'pending');
        $this->makeEvent($requester, $activeRoom, $date, '06:00', '07:00', 'moved');
        $this->makeEvent($requester, $inactiveRoom, $date, '13:00', '14:00', 'approved');

        $this->getJson('/api/tv-display/agenda')->assertOk()
            ->assertJsonPath('date', $date)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.room_name', $activeRoom->room_name)
            ->assertJsonPath('data.0.event_name', 'Agenda approved')
            ->assertJsonPath('data.0.start_time', '09:00')
            ->assertJsonPath('data.0.end_time', '10:00')
            ->assertJsonPath('data.0.status', 'approved');
    }

    public function test_requester_and_reporter_are_derived_from_the_authenticated_user(): void
    {
        Storage::fake('public');
        $requester = $this->makeUser('Guru');
        $spoofedUser = $this->makeUser('Organisasi');
        $room = $this->makeRoom($this->makeUser('PIC'));
        $date = now()->addDays(2)->format('Y-m-d');

        $eventResponse = $this->postJson('/api/events', [
            ...$this->booking($room, $date, '09:00', '10:00'),
            'requester_id' => $spoofedUser->id,
            'pic_id' => $spoofedUser->id,
            'status' => 'approved',
            'approved_by' => $spoofedUser->id,
        ], $this->headersFor($requester))->assertCreated();

        $this->assertDatabaseHas('events', [
            'id' => $eventResponse->json('data.id'),
            'requester_id' => $requester->id,
            'pic_id' => $requester->id,
            'status' => 'pending',
            'approved_by' => null,
        ]);

        $complaintResponse = $this->postJson('/api/complaints', [
            'room_id' => $room->id,
            'description' => 'Laporan uji identitas server',
            'photo' => UploadedFile::fake()->image('laporan.png'),
            'reporter_id' => $spoofedUser->id,
            'handler_id' => $spoofedUser->id,
            'status_repair' => 'Sudah Diperbaiki',
        ], $this->headersFor($requester))->assertCreated();

        $this->assertDatabaseHas('complaints', [
            'id' => $complaintResponse->json('data.id'),
            'reporter_id' => $requester->id,
            'handler_id' => null,
            'status_repair' => 'Belum Diperbaiki',
        ]);
    }

    public function test_upload_size_is_limited_and_public_room_route_is_read_only(): void
    {
        Storage::fake('public');
        $guru = $this->makeUser('Guru');
        $room = $this->makeRoom($this->makeUser('PIC'));

        $this->postJson('/api/rooms', [])->assertMethodNotAllowed();
        $this->postJson('/api/admin/rooms', [])->assertUnauthorized();

        $this->postJson('/api/complaints', [
            'room_id' => $room->id,
            'description' => 'Foto terlalu besar',
            'photo' => UploadedFile::fake()->image('terlalu-besar.jpg')->size(8193),
        ], $this->headersFor($guru))->assertUnprocessable()->assertJsonValidationErrors('photo');
    }

    public function test_requester_id_is_non_nullable_after_migrations(): void
    {
        $column = collect(Schema::getColumns('events'))->firstWhere('name', 'requester_id');

        $this->assertNotNull($column);
        $this->assertFalse($column['nullable']);
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

    private function makeRoom(User $pic, string $type = 'lainnya'): Room
    {
        return Room::create([
            'room_name' => 'Ruang Uji '.uniqid(),
            'room_type' => $type,
            'capacity' => 30,
            'location' => 'Gedung Uji',
            'pic_id' => $pic->id,
            'is_active' => true,
        ]);
    }

    private function makeEvent(User $requester, Room $room, string $date, string $start, string $end, string $status): Event
    {
        return Event::create([
            'room_id' => $room->id,
            'pic_id' => $requester->id,
            'requester_id' => $requester->id,
            'event_name' => 'Agenda '.$status,
            'date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'status' => $status,
        ]);
    }

    private function booking(Room $room, string $date, string $start, string $end): array
    {
        return [
            'room_id' => $room->id,
            'event_name' => 'Agenda baru',
            'date' => $date,
            'start_time' => $start,
            'end_time' => $end,
        ];
    }

    private function headersFor(User $user): array
    {
        Auth::forgetGuards();

        return ['Authorization' => 'Bearer '.$user->createToken('rapi-api-test')->plainTextToken];
    }
}