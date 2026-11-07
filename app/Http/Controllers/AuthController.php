<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Room;
use App\Services\TodayAgendaService;
use Carbon\Carbon;

class AuthController extends Controller {
    // Menampilkan halaman landing page & form login
    public function showLogin(TodayAgendaService $agenda) {
        $now = Carbon::now()->format('H:i:s');

        $eventsToday = $agenda->get()
            ->map(function ($event) use ($now) {
                if ($now >= $event->start_time && $now <= $event->end_time) {
                    $event->live_status = 'Sedang Dipakai';
                } elseif ($now < $event->start_time) {
                    $event->live_status = 'Akan Datang';
                } else {
                    $event->live_status = 'Selesai';
                }

                $event->event_name = $event->title ?? $event->event_name ?? 'Kegiatan';

                return $event;
            });

        // Data Ruangan untuk Landing Page
        $allRooms = Room::query()
            ->leftJoin('users as room_pic', 'rooms.pic_id', '=', 'room_pic.id')
            ->where('rooms.is_active', true)
            ->orderBy('rooms.room_name')
            ->select('rooms.*', 'room_pic.fullname as pic_name', 'room_pic.phone_number as pic_phone')
            ->get();

        $roomCount = $allRooms->count();
        $todayBookedRoomIds = $eventsToday->pluck('room_id')->unique()->all();
        $emptyRoomsCount = $allRooms->whereNotIn('id', $todayBookedRoomIds)->count();
        $emptyRooms = $allRooms->whereNotIn('id', $todayBookedRoomIds)->values();

        // Statistik umum
        $totalEventsCount = DB::table('events')->whereIn('status', ['approved', 'moved'])->count();
        $totalComplaintsCount = DB::table('complaints')->count();
        $resolvedComplaintsCount = DB::table('complaints')->where('status_repair', 'Sudah Diperbaiki')->count();

        return view('welcome', compact(
            'eventsToday',
            'allRooms',
            'roomCount',
            'emptyRoomsCount',
            'emptyRooms',
            'totalEventsCount',
            'totalComplaintsCount',
            'resolvedComplaintsCount'
        ));
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

            if (in_array($role, ['Admin', 'Waka'], true)) {
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