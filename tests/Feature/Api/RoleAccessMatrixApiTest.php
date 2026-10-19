<?php

namespace Tests\Feature\Api;

use App\Models\Complaint;
use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RoleAccessMatrixApiTest extends TestCase
{
    use RefreshDatabase;

    private array $tokens = [];

    public function test_all_roles_can_view_public_data_and_their_authenticated_profile(): void
    {
        $date = now()->addDays(2)->format('Y-m-d');
        $this->getJson('/api/calendar/events?from='.$date.'&to='.$date)->assertOk();
        $this->getJson('/api/rooms')->assertOk();
        $this->getJson('/api/me')->assertUnauthorized();

        foreach (['Admin', 'PIC', 'Guru', 'Organisasi'] as $role) {
            $user = $this->makeUser($role);
            $login = $this->postJson('/api/login', [
                'username' => $user->username,
                'password' => 'password123',
                'device_name' => 'role-matrix-test',
            ])->assertOk()->assertJsonPath('user.role', $role);

            $headers = ['Authorization' => 'Bearer '.$login->json('token')];
            Auth::forgetGuards();
            $this->getJson('/api/me', $headers)->assertOk()
                ->assertJsonPath('user.id', $user->id);
            $this->getJson('/api/calendar/events?from='.$date.'&to='.$date, $headers)->assertOk();
            $this->getJson('/api/rooms', $headers)->assertOk();
        }
    }

    public function test_booking_and_complaint_creation_match_role_matrix(): void
    {
        Storage::fake('public');
        $users = $this->makeUsers();
        $picRoom = $this->makeRoom($users['PIC']);
        $date = now()->addDays(4)->format('Y-m-d');

        $this->postJson('/api/events', [
            'room_id' => $picRoom->id,
            'event_name' => 'Guest booking',
            'date' => $date,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ])->assertUnauthorized();
        $this->postJson('/api/complaints', [])->assertUnauthorized();

        foreach (['Admin', 'Guru', 'Organisasi'] as $index => $role) {
            $user = $users[$role];
            $startHour = 9 + $index * 2;
            $this->postJson('/api/events', [
                'room_id' => $picRoom->id,
                'event_name' => 'Agenda '.$role,
                'date' => $date,
                'start_time' => sprintf('%02d:00', $startHour),
                'end_time' => sprintf('%02d:00', $startHour + 1),
            ], $this->headersFor($user))->assertCreated();

            $this->postJson('/api/complaints', [
                'room_id' => $picRoom->id,
                'description' => 'Laporan '.$role,
                'photo' => UploadedFile::fake()->image('laporan-'.$role.'.png'),
            ], $this->headersFor($user))->assertCreated();
        }

        $this->postJson('/api/events', [
            'room_id' => $picRoom->id,
            'event_name' => 'Agenda PIC',
            'date' => $date,
            'start_time' => '16:00',
            'end_time' => '17:00',
        ], $this->headersFor($users['PIC']))->assertForbidden();
        $this->postJson('/api/complaints', [
            'room_id' => $picRoom->id,
            'description' => 'Laporan PIC',
            'photo' => UploadedFile::fake()->image('laporan-pic.png'),
        ], $this->headersFor($users['PIC']))->assertForbidden();
    }

    public function test_complaint_visibility_and_status_are_scoped_to_reporter_or_assigned_pic(): void
    {
        $users = $this->makeUsers();
        $otherPic = $this->makeUser('PIC');
        $assignedRoom = $this->makeRoom($users['PIC']);
        $otherRoom = $this->makeRoom($otherPic);
        $guruComplaint = $this->makeComplaint($users['Guru'], $assignedRoom);
        $orgComplaint = $this->makeComplaint($users['Organisasi'], $otherRoom);

        $this->getJson('/api/complaints', $this->headersFor($users['Admin']))->assertOk()
            ->assertJsonFragment(['id' => $guruComplaint->id])
            ->assertJsonFragment(['id' => $orgComplaint->id]);
        $this->getJson('/api/complaints', $this->headersFor($users['PIC']))->assertOk()
            ->assertJsonFragment(['id' => $guruComplaint->id])
            ->assertJsonMissing(['id' => $orgComplaint->id]);
        $this->getJson('/api/complaints', $this->headersFor($users['Guru']))->assertOk()
            ->assertJsonFragment(['id' => $guruComplaint->id])
            ->assertJsonMissing(['id' => $orgComplaint->id]);
        $this->getJson('/api/complaints', $this->headersFor($users['Organisasi']))->assertOk()
            ->assertJsonFragment(['id' => $orgComplaint->id])
            ->assertJsonMissing(['id' => $guruComplaint->id]);

        $payload = ['status_repair' => 'Menunggu Spare Part'];
        $url = '/api/complaints/'.$guruComplaint->id.'/status';
        Auth::forgetGuards();
        $this->patchJson($url, $payload)->assertUnauthorized();
        foreach (['Admin', 'Guru', 'Organisasi'] as $role) {
            $this->patchJson($url, $payload, $this->headersFor($users[$role]))->assertForbidden();
        }
        $this->patchJson($url, $payload, $this->headersFor($otherPic))->assertForbidden();
        $this->patchJson($url, $payload, $this->headersFor($users['PIC']))->assertOk()
            ->assertJsonPath('data.status_repair', 'Menunggu Spare Part');
    }

    public function test_only_admin_can_move_manage_master_data_or_create_whatsapp_escalation(): void
    {
        Config::set('rapi.escalation_hours', 24);
        $users = $this->makeUsers();
        $sourceRoom = $this->makeRoom($users['PIC']);
        $targetRoom = $this->makeRoom($users['PIC']);
        $date = now()->addDays(6)->format('Y-m-d');
        $event = Event::create([
            'room_id' => $sourceRoom->id,
            'pic_id' => $users['Guru']->id,
            'requester_id' => $users['Guru']->id,
            'event_name' => 'Agenda matrix',
            'date' => $date,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => 'pending',
        ]);
        $complaint = $this->makeComplaint($users['Guru'], $sourceRoom, now()->subHours(25));

        $this->getJson('/api/admin/users')->assertUnauthorized();
        $this->getJson('/api/admin/rooms')->assertUnauthorized();
        $this->postJson('/api/events/'.$event->id.'/move', [
            'new_room_id' => $targetRoom->id,
            'move_reason' => 'Guest tidak diizinkan',
        ])->assertUnauthorized();
        $this->getJson('/api/admin/complaints/'.$complaint->id.'/whatsapp')->assertUnauthorized();
        $this->getJson('/api/admin/users', $this->headersFor($users['Admin']))->assertOk();
        $this->getJson('/api/admin/rooms', $this->headersFor($users['Admin']))->assertOk();
        foreach (['PIC', 'Guru', 'Organisasi'] as $role) {
            $headers = $this->headersFor($users[$role]);
            $this->getJson('/api/admin/users', $headers)->assertForbidden();
            $this->getJson('/api/admin/rooms', $headers)->assertForbidden();
            $this->postJson('/api/events/'.$event->id.'/move', [
                'new_room_id' => $targetRoom->id,
                'move_reason' => 'Uji role matrix',
            ], $headers)->assertForbidden();
            $this->getJson('/api/admin/complaints/'.$complaint->id.'/whatsapp', $headers)
                ->assertForbidden();
        }

        $this->postJson('/api/events/'.$event->id.'/move', [
            'new_room_id' => $targetRoom->id,
            'move_reason' => 'Dipindahkan oleh Admin',
        ], $this->headersFor($users['Admin']))->assertOk();
        $this->getJson('/api/admin/complaints/'.$complaint->id.'/whatsapp', $this->headersFor($users['Admin']))
            ->assertOk()->assertJsonPath('url', fn (string $url) => str_starts_with($url, 'https://wa.me/62'));
    }

    private function makeUsers(): array
    {
        $users = [];
        foreach (['Admin', 'PIC', 'Guru', 'Organisasi'] as $role) {
            $users[$role] = $this->makeUser($role);
        }

        return $users;
    }

    private function makeUser(string $roleName): User
    {
        $role = Role::firstOrCreate(['role_name' => $roleName]);

        return User::create([
            'username' => strtolower($roleName).'_'.uniqid(),
            'password' => Hash::make('password123'),
            'fullname' => $roleName.' Matrix Tester',
            'phone_number' => '081234567890',
            'role_id' => $role->id,
        ]);
    }

    private function makeRoom(User $pic): Room
    {
        return Room::create([
            'room_name' => 'Ruang Matrix '.uniqid(),
            'capacity' => 30,
            'location' => 'Gedung Uji',
            'pic_id' => $pic->id,
            'is_active' => true,
        ]);
    }

    private function makeComplaint(User $reporter, Room $room, $reportedAt = null): Complaint
    {
        return Complaint::create([
            'room_id' => $room->id,
            'reporter_id' => $reporter->id,
            'description' => 'Keluhan role matrix',
            'photo_path' => 'complaints/matrix.jpg',
            'status_repair' => 'Belum Diperbaiki',
            'reported_at' => $reportedAt ?? now(),
        ]);
    }

    private function headersFor(User $user): array
    {
        if (!isset($this->tokens[$user->id])) {
            $this->tokens[$user->id] = $user->createToken('role-matrix-test')->plainTextToken;
        }

        Auth::forgetGuards();

        return ['Authorization' => 'Bearer '.$this->tokens[$user->id]];
    }
}