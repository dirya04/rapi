<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function dashboardPath(Request $request): string
    {
        return match ($request->user()->role?->role_name) {
            'PIC' => '/pic/dashboard',
            'Guru', 'Organisasi' => '/guru/dashboard',
            default => '/admin/dashboard',
        };
    }
}
