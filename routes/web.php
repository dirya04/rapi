<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\RoomController as AdminRoomController;

// --------------------------------------------------------------------------
// HELPER QUERY DATA
// --------------------------------------------------------------------------

// Query agenda lengkap dengan nama ruangan
$getEvents = function (?int $picId = null, ?int $requesterId = null) {
    $query = DB::table('events')
        ->join('rooms', 'events.room_id', '=', 'rooms.id')
        ->select('events.*', 'rooms.room_name')
        ->orderByDesc('events.date')
        ->orderBy('events.start_time');

    if ($picId !== null) {
        $query->where('rooms.pic_id', $picId);
    }

    if ($requesterId !== null) {
        $query->where('events.requester_id', $requesterId);
    }

    return $query->get();
};

// Query pengaduan kerusakan lengkap dengan nama ruangan dan pelapor
$getComplaints = function (?int $picId = null, ?int $reporterId = null) {
    $query = DB::table('complaints')
        ->join('rooms', 'complaints.room_id', '=', 'rooms.id')
        ->leftJoin('users as pic', 'rooms.pic_id', '=', 'pic.id')
        ->leftJoin('users as reporter', 'complaints.reporter_id', '=', 'reporter.id')
        ->select('complaints.*', 'rooms.room_name', 'rooms.pic_id', 'pic.fullname as pic_name', 'pic.phone_number as pic_phone', 'reporter.fullname as reporter_name')
        ->orderByDesc('complaints.reported_at');

    if ($picId !== null) {
        $query->where('rooms.pic_id', $picId);
    }

    if ($reporterId !== null) {
        $query->where('complaints.reporter_id', $reporterId);
    }

    return $query->get();
};

// --------------------------------------------------------------------------
// HALAMAN UTAMA / KALENDER PUBLIK (Read-Only untuk Siswa & Publik)
// --------------------------------------------------------------------------
Route::get('/', [AuthController::class, 'showLogin'])->name('public.calendar');

Route::view('/calendar', 'calendar')->name('calendar.index');

// --------------------------------------------------------------------------
// AUTENTIKASI & LUPA PASSWORD VIA WHATSAPP OTP
// --------------------------------------------------------------------------
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])->name('password.forgot.form');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendOtp'])
    ->middleware('throttle:5,1')->name('password.forgot.send');
Route::get('/reset-password', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset.form');
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])
    ->middleware('throttle:5,1')->name('password.reset.submit');

// --------------------------------------------------------------------------
// DASHBOARD & HAK AKSES BERDASARKAN ROLE
// --------------------------------------------------------------------------
Route::middleware(['auth'])->group(function () use ($getEvents, $getComplaints) {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // 1. Dashboard Admin / Waka
    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])
        ->middleware('role:Admin,Waka')->name('admin.dashboard');

    // 2. Dashboard PIC Ruangan
    Route::get('/pic/dashboard', [DashboardController::class, 'pic'])
        ->middleware('role:PIC')->name('pic.dashboard');

    // 3. Dashboard Guru / Organisasi
    Route::get('/guru/dashboard', [DashboardController::class, 'guru'])
        ->middleware('role:Guru,Organisasi')->name('guru.dashboard');

    // Cetak Laporan PDF
    Route::get('/report/print', function () use ($getEvents, $getComplaints) {
        $user = auth()->user();
        $role = $user->role?->role_name;

        if (in_array($role, ['Admin', 'Waka'], true)) {
            $events = $getEvents();
            $complaints = $getComplaints();
        } elseif ($role === 'PIC') {
            $events = $getEvents(picId: $user->id);
            $complaints = $getComplaints(picId: $user->id);
        } else {
            $events = $getEvents(requesterId: $user->id);
            $complaints = $getComplaints(reporterId: $user->id);
        }

        return view('print_report', compact('events', 'complaints'));
    })->middleware('role:Admin,Waka,PIC,Guru,Organisasi')->name('report.print');

    // ----------------------------------------------------------------------
    // PROSES TRANSAKSI (FORM & AKSI)
    // ----------------------------------------------------------------------

    // Ajukan Peminjaman Ruangan (Admin, Guru, Organisasi, PIC)
    Route::post('/events', [EventController::class, 'store'])
        ->middleware('role:Admin,Waka,Guru,Organisasi,PIC')->name('events.store');
    Route::post('/events/{event}/decision', [EventController::class, 'decide'])
        ->middleware('role:Admin,Waka')->name('events.decide');

    // Pindahkan Ruangan / Reschedule (Khusus Admin/Waka)
    Route::post('/events/{id}/move', [EventController::class, 'move'])
        ->middleware('role:Admin,Waka')->name('events.move');

    Route::get('/complaints/{complaint}/whatsapp', [ComplaintController::class, 'whatsapp'])
        ->middleware('role:Admin,Waka')->name('complaints.whatsapp');

    // Lapor Kerusakan Fasilitas (Admin, Guru, Organisasi, PIC)
    Route::post('/complaints', [ComplaintController::class, 'store'])
        ->middleware('role:Admin,Waka,Guru,Organisasi,PIC')->name('complaints.store');

    // Update Status Laporan Kerusakan (Khusus PIC / Sarpras / Admin)
    Route::post('/complaints/{id}/status', [ComplaintController::class, 'updateStatus'])
        ->middleware('role:PIC')->name('complaints.status');

    // ----------------------------------------------------------------------
    // MANAJEMEN PENGGUNA (KHUSUS ADMIN / WAKA)
    // ----------------------------------------------------------------------
    Route::post('/admin/users', [AdminUserController::class, 'store'])
        ->middleware('role:Admin,Waka')->name('admin.users.store');
    Route::put('/admin/users/{user}', [AdminUserController::class, 'update'])
        ->middleware('role:Admin,Waka')->name('admin.users.update');
    Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy'])
        ->middleware('role:Admin,Waka')->name('admin.users.destroy');
    Route::post('/admin/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])
        ->middleware('role:Admin')->name('admin.users.reset-password');

    // ----------------------------------------------------------------------
    // MANAJEMEN RUANGAN (KHUSUS ADMIN / WAKA)
    // ----------------------------------------------------------------------
    Route::post('/admin/rooms', [AdminRoomController::class, 'store'])
        ->middleware('role:Admin,Waka')->name('admin.rooms.store');
    Route::put('/admin/rooms/{room}', [AdminRoomController::class, 'update'])
        ->middleware('role:Admin,Waka')->name('admin.rooms.update');
    Route::delete('/admin/rooms/{room}', [AdminRoomController::class, 'destroy'])
        ->middleware('role:Admin,Waka')->name('admin.rooms.destroy');
});
