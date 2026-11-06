<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedLoginEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_and_login_routes_show_the_same_split_screen_entry(): void
    {
        $this->withoutVite();

        foreach (['/', '/login'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('Daftar Agenda Hari Ini')
                ->assertSee('Login Sistem')
                ->assertSee('Masuk Ke Sistem')
                ->assertSee('Lupa Password via WhatsApp OTP?')
                ->assertSee('Belum ada agenda hari ini')
                ->assertSee(route('login.post'));
        }
    }
}
