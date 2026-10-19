<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ComplaintController;

// Agenda dan nama ruangan untuk dashboard/laporan.
$getEvents = fn () => DB::table('events')
    ->join('rooms', 'events.room_id', '=', 'rooms.id')
    ->select('events.*', 'rooms.room_name')
    ->orderByDesc('events.date')
    ->orderBy('events.start_time')
    ->get();

// Pengaduan dan nama ruangan.
$getComplaints = fn () => DB::table('complaints')
    ->join('rooms', 'complaints.room_id', '=', 'rooms.id')
    ->select('complaints.*', 'rooms.room_name')
    ->orderByDesc('complaints.reported_at')
    ->get();

Route::get('/', function () {
    return '<h1>Halaman Kalender Publik (Read-Only)</h1><a href="/login">Login Ke Aplikasi</a>';
})->name('public.calendar');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () use ($getEvents, $getComplaints) {
    Route::get('/admin/dashboard', function () use ($getEvents, $getComplaints) {
        $rooms = DB::table('rooms')->where('is_active', true)->orderBy('room_name')->get();
        $events = $getEvents();
        $complaints = $getComplaints();

        return view('admin.dashboard', compact('rooms', 'events', 'complaints'));
    });

    Route::get('/report/print', function () use ($getEvents, $getComplaints) {
        $events = $getEvents();
        $complaints = $getComplaints();

        return view('print_report', compact('events', 'complaints'));
    })->name('report.print');

    Route::post('/events', [EventController::class, 'store'])
        ->middleware('role:Admin,Guru,Organisasi')->name('events.store');
    Route::post('/events/{id}/move', [EventController::class, 'move'])
        ->middleware('role:Admin')->name('events.move');
    Route::post('/complaints', [ComplaintController::class, 'store'])
        ->middleware('role:Admin,Guru,Organisasi')->name('complaints.store');
    Route::post('/complaints/{id}/status', [ComplaintController::class, 'updateStatus'])
        ->middleware('role:PIC')->name('complaints.status');

    Route::get('/pic/dashboard', function () {
        return view('pic.dashboard');
    });

    Route::get('/guru/dashboard', function () {
        return view('guru.dashboard');
    });
});
