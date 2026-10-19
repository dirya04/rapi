<?php

namespace Tests\Feature\Api;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_get_profile_and_revoke_current_token(): void
    {
        $role = Role::create(['role_name' => 'Guru']);
        User::create([
            'username' => 'guru_api',
            'password' => Hash::make('rahasia123'),
            'fullname' => 'Guru API',
            'phone_number' => '081234567890',
            'role_id' => $role->id,
        ]);

        $login = $this->postJson('/api/login', [
            'username' => 'guru_api',
            'password' => 'rahasia123',
            'device_name' => 'test-device',
        ])->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.role', 'Guru');

        $token = $login->json('token');
        $headers = ['Authorization' => 'Bearer '.$token];

        $this->getJson('/api/me', $headers)
            ->assertOk()
            ->assertJsonPath('user.username', 'guru_api');

        $this->postJson('/api/logout', [], $headers)
            ->assertOk()
            ->assertJsonPath('message', 'Token berhasil dicabut.');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => explode('|', $token, 2)[0],
        ]);
        Auth::forgetGuards();
        $this->getJson('/api/me', $headers)->assertUnauthorized();
    }

    public function test_invalid_credentials_do_not_issue_a_token(): void
    {
        $role = Role::create(['role_name' => 'Guru']);
        User::create([
            'username' => 'guru_api',
            'password' => Hash::make('rahasia123'),
            'fullname' => 'Guru API',
            'phone_number' => '081234567890',
            'role_id' => $role->id,
        ]);

        $this->postJson('/api/login', [
            'username' => 'guru_api',
            'password' => 'salah',
        ])->assertUnprocessable()->assertJsonMissingPath('token');
    }
}
