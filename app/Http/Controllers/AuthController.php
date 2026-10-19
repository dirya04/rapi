<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller {
    // Menampilkan halaman form login
    public function showLogin() {
        return view('auth.login');
    }

    // Proses autentikasi login & redirect sesuai role
    public function login(Request $request) {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();
            $role = $user->role->role_name;

            // Dipakai tampilan dashboard (nama dan peran pengguna)
            session([
                'fullname'  => $user->fullname,
                'role_name' => $role,
            ]);

            if ($role === 'Admin') {
                return redirect()->intended('/admin/dashboard');
            } elseif ($role === 'PIC') {
                return redirect()->intended('/pic/dashboard');
            } else {
                return redirect()->intended('/guru/dashboard');
            }
        }

        return back()->withErrors([
            'username' => 'Username atau password salah.',
        ])->onlyInput('username');
    }

    // Proses logout
    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}