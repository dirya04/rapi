<?php

namespace Tests\Feature\Api;

use App\Models\Complaint;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterDataApiTest extends TestCase
{
    use RefreshDatabase;

    private array $tokens = [];

    public function test_admin_can_manage_users_and_non_admin_cannot(): void
    {
        $admin = $this->makeUser('Admin');
        $guru = $this->makeUser('Guru');
        $picRole = Role::create(['role_name' => 'PIC']);

        $this->getJson('/api/admin/users', $this->headersFor($guru))->assertForbidden();

        $created = $this->postJson('/api/admin/users', [
            'username' => 'pic_baru',
            'fullname' => 'PIC Baru',
            'phone_number' => '081234567890',
            'password' => 'password123',
            'role_id' => $picRole->id,
        ], $this->headersFor($admin))->assertCreated()
            ->assertJsonPath('data.username', 'pic_baru')
            ->assertJsonPath('data.role', 'PIC')
            ->assertJsonMissingPath('data.password');

        $userId = $created->json('data.id');
        $this->putJson('/api/admin/users/'.$userId, [
            'fullname' => 'PIC Diperbarui',
        ], $this->headersFor($admin))->assertOk()
            ->assertJsonPath('data.fullname', 'PIC Diperbarui');

        $this->deleteJson('/api/admin/users/'.$admin->id, [], $this->headersFor($admin))
            ->assertUnprocessable();
        $this->deleteJson('/api/admin/users/'.$userId, [], $this->headersFor($admin))
            ->assertOk()->assertJsonPath('message', 'Akun dihapus.');
    }

    public function test_admin_can_assign_waka_role_to_a_user(): void
    {
        $admin = $this->makeUser('Admin');
        $user = $this->makeUser('Guru');
        $wakaRole = Role::firstOrCreate(['role_name' => 'Waka']);

        $this->putJson('/api/admin/users/'.$user->id, [
            'role_id' => $wakaRole->id,
        ], $this->headersFor($admin))->assertOk()
            ->assertJsonPath('data.role', 'Waka');
    }

    public function test_room_crud_validates_pic_and_public_endpoint_hides_inactive_rooms(): void
    {
        $admin = $this->makeUser('Admin');
        $pic = $this->makeUser('PIC');
        $guru = $this->makeUser('Guru');
        $activeRoom = $this->makeRoom($pic, 'Ruang Aktif', true);
        $this->makeRoom($pic, 'Ruang Nonaktif', false);

        $this->getJson('/api/rooms')->assertOk()
            ->assertJsonFragment(['id' => $activeRoom->id, 'room_name' => 'Ruang Aktif'])
            ->assertJsonMissing(['room_name' => 'Ruang Nonaktif']);
        $this->getJson('/api/admin/rooms', $this->headersFor($guru))->assertForbidden();

        $this->postJson('/api/admin/rooms', [
            'room_name' => 'Ruang Tidak Valid',
            'room_type' => 'lainnya',
            'capacity' => 30,
            'location' => 'Gedung A',
            'pic_id' => $guru->id,
        ], $this->headersFor($admin))->assertUnprocessable()->assertJsonValidationErrors('pic_id');

        $this->postJson('/api/admin/rooms', [
            'room_name' => 'Ruang Tanpa Tipe',
            'capacity' => 30,
            'location' => 'Gedung A',
            'pic_id' => $pic->id,
        ], $this->headersFor($admin))->assertUnprocessable()->assertJsonValidationErrors('room_type');

        $created = $this->postJson('/api/admin/rooms', [
            'room_name' => 'Ruang Baru',
            'room_type' => 'lainnya',
            'capacity' => 30,
            'location' => 'Gedung A',
            'pic_id' => $pic->id,
        ], $this->headersFor($admin))->assertCreated();
        $roomId = $created->json('data.id');

        $this->putJson('/api/admin/rooms/'.$roomId, [
            'room_name' => 'Ruang Baru Diperbarui',
            'room_type' => 'lainnya',
            'capacity' => 35,
            'location' => 'Gedung B',
            'pic_id' => $pic->id,
            'is_active' => false,
        ], $this->headersFor($admin))->assertOk()
            ->assertJsonPath('data.room_name', 'Ruang Baru Diperbarui')
            ->assertJsonPath('data.is_active', false);

        $this->deleteJson('/api/admin/rooms/'.$roomId, [], $this->headersFor($admin))
            ->assertOk()->assertJsonPath('message', 'Ruangan dihapus.');
    }

    public function test_user_and_room_with_references_cannot_be_deleted(): void
    {
        $admin = $this->makeUser('Admin');
        $pic = $this->makeUser('PIC');
        $reporter = $this->makeUser('Guru');
        $room = $this->makeRoom($pic, 'Ruang Berreferensi');
        Complaint::create([
            'room_id' => $room->id,
            'reporter_id' => $reporter->id,
            'description' => 'Kerusakan untuk uji referensi',
            'photo_path' => 'complaints/test.jpg',
            'status_repair' => 'Belum Diperbaiki',
            'reported_at' => now(),
        ]);

        $this->deleteJson('/api/admin/users/'.$pic->id, [], $this->headersFor($admin))
            ->assertUnprocessable();
        $this->deleteJson('/api/admin/rooms/'.$room->id, [], $this->headersFor($admin))
            ->assertUnprocessable();
    }

    public function test_pic_role_cannot_be_changed_while_assigned_to_an_active_room(): void
    {
        $admin = $this->makeUser('Admin');
        $pic = $this->makeUser('PIC');
        $room = $this->makeRoom($pic, 'Ruang PIC Aktif');
        $guruRole = Role::firstOrCreate(['role_name' => 'Guru']);
        $headers = $this->headersFor($admin);

        $this->putJson('/api/admin/users/'.$pic->id, ['role_id' => $guruRole->id], $headers)
            ->assertUnprocessable()->assertJsonValidationErrors('role_id');
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'pic_id' => $pic->id]);

        $replacementPic = $this->makeUser('PIC');
        $room->update(['pic_id' => $replacementPic->id]);
        $this->putJson('/api/admin/users/'.$pic->id, ['role_id' => $guruRole->id], $headers)
            ->assertOk()->assertJsonPath('data.role', 'Guru');
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

    private function makeRoom(User $pic, string $name, bool $active = true): Room
    {
        return Room::create([
            'room_name' => $name,
            'capacity' => 30,
            'location' => 'Gedung Uji',
            'pic_id' => $pic->id,
            'is_active' => $active,
        ]);
    }

    private function headersFor(User $user): array
    {
        Auth::forgetGuards();
        $this->tokens[$user->id] ??= $user->createToken('master-data-test')->plainTextToken;

        return ['Authorization' => 'Bearer '.$this->tokens[$user->id]];
    }
}