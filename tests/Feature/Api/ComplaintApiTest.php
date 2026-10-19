<?php

namespace Tests\Feature\Api;

use App\Models\Complaint;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComplaintApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_reporter_can_upload_compressed_photo_and_only_assigned_pic_can_update_status(): void
    {
        Storage::fake('public');
        $reporter = $this->makeUser('Guru');
        $assignedPic = $this->makeUser('PIC');
        $otherPic = $this->makeUser('PIC');
        $room = $this->makeRoom($assignedPic);

        $response = $this->postJson('/api/complaints', [
            'room_id' => $room->id,
            'description' => 'Proyektor tidak menyala',
            'photo' => UploadedFile::fake()->image('bukti.png', 2000, 1200),
        ], $this->headersFor($reporter))->assertCreated()
            ->assertJsonPath('data.status_repair', 'Belum Diperbaiki');

        $complaintId = $response->json('data.id');
        $path = $response->json('data.photo_path');
        $this->assertStringEndsWith('.jpg', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertDatabaseHas('complaints', [
            'id' => $complaintId,
            'reporter_id' => $reporter->id,
            'photo_path' => $path,
        ]);

        $payload = ['status_repair' => 'Menunggu Spare Part'];
        $url = '/api/complaints/'.$complaintId.'/status';
        $this->patchJson($url, $payload, $this->headersFor($otherPic))->assertForbidden();
        $this->patchJson($url, $payload, $this->headersFor($assignedPic))
            ->assertOk()
            ->assertJsonPath('data.status_repair', 'Menunggu Spare Part');

        $this->assertDatabaseHas('complaints', [
            'id' => $complaintId,
            'handler_id' => $assignedPic->id,
            'status_repair' => 'Menunggu Spare Part',
        ]);
    }

    public function test_complaint_list_is_limited_to_reporter_or_pic_rooms(): void
    {
        $reporter = $this->makeUser('Guru');
        $pic = $this->makeUser('PIC');
        $otherPic = $this->makeUser('PIC');
        $room = $this->makeRoom($pic);
        $otherRoom = $this->makeRoom($otherPic);
        $mine = $this->makeComplaint($reporter, $room);
        $other = $this->makeComplaint($this->makeUser('Guru'), $otherRoom);

        $this->getJson('/api/complaints', $this->headersFor($pic))
            ->assertOk()->assertJsonFragment(['id' => $mine->id])->assertJsonMissing(['id' => $other->id]);
        $this->getJson('/api/complaints', $this->headersFor($reporter))
            ->assertOk()->assertJsonFragment(['id' => $mine->id])->assertJsonMissing(['id' => $other->id]);
    }

    public function test_whatsapp_escalation_requires_admin_old_unresolved_complaint_and_valid_pic_number(): void
    {
        $admin = $this->makeUser('Admin');
        $guru = $this->makeUser('Guru');
        $pic = $this->makeUser('PIC');
        $room = $this->makeRoom($pic);
        $complaint = $this->makeComplaint($guru, $room, now()->subHours(25));
        $url = '/api/admin/complaints/'.$complaint->id.'/whatsapp';

        $this->getJson($url, $this->headersFor($guru))->assertForbidden();
        $this->getJson($url, $this->headersFor($admin))
            ->assertOk()
            ->assertJsonPath('url', fn (string $value) => str_starts_with($value, 'https://wa.me/62'))
            ->assertJsonPath('message', fn (string $message) => str_contains($message, 'Nomor laporan: #'.$complaint->id)
                && str_contains($message, 'Ruang: '.$room->room_name)
                && str_contains($message, 'Detail: Keluhan uji'));

        $freshComplaint = $this->makeComplaint($guru, $room, now()->subHours(2));
        $this->getJson('/api/admin/complaints/'.$freshComplaint->id.'/whatsapp', $this->headersFor($admin))
            ->assertUnprocessable();

        $repaired = $this->makeComplaint($guru, $room, now()->subHours(48), 'Sudah Diperbaiki');
        $this->getJson('/api/admin/complaints/'.$repaired->id.'/whatsapp', $this->headersFor($admin))
            ->assertUnprocessable();
    }

    public function test_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');
        $reporter = $this->makeUser('Guru');
        $pic = $this->makeUser('PIC');
        $room = $this->makeRoom($pic);

        $this->postJson('/api/complaints', [
            'room_id' => $room->id,
            'description' => 'Berkas salah',
            'photo' => UploadedFile::fake()->create('bukti.txt', 12, 'text/plain'),
        ], $this->headersFor($reporter))->assertUnprocessable()->assertJsonValidationErrors('photo');
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

    private function makeRoom(User $pic): Room
    {
        return Room::create([
            'room_name' => 'Ruang Uji '.uniqid(),
            'capacity' => 30,
            'location' => 'Gedung Uji',
            'pic_id' => $pic->id,
            'is_active' => true,
        ]);
    }

    private function makeComplaint(User $reporter, Room $room, $reportedAt = null, string $status = 'Belum Diperbaiki'): Complaint
    {
        return Complaint::create([
            'room_id' => $room->id,
            'reporter_id' => $reporter->id,
            'description' => 'Keluhan uji',
            'photo_path' => 'complaints/example.jpg',
            'status_repair' => $status,
            'reported_at' => $reportedAt ?? now(),
        ]);
    }

    private function headersFor(User $user): array
    {
        Auth::forgetGuards();

        return ['Authorization' => 'Bearer '.$user->createToken('test-device')->plainTextToken];
    }
}
