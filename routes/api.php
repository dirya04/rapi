<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\TvDisplayController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// --------------------------------------------------------------------------
// WEBHOOK DEPLOY & MIGRASI DATABASE (Shared Hosting cPanel)
// --------------------------------------------------------------------------
Route::get('/deploy-migrate-{secret}', function (Request $request, string $secret) {
    $expectedSecret = (string) config('app.deploy_secret');

    if (!hash_equals($expectedSecret, $secret)) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Forbidden: Invalid deployment secret token.',
        ], 403);
    }

    $results = [];

    // 1. Jalankan Migrasi Database
    Artisan::call('migrate', ['--force' => true]);
    $results['migrate'] = trim(Artisan::output());

    // 2. Jalankan Seeder jika parameter ?seed=1 diberikan
    if ($request->query('seed') === '1' || $request->boolean('seed')) {
        Artisan::call('db:seed', ['--force' => true]);
        $results['seed'] = trim(Artisan::output());
    }

    // 3. Buat Symlink Storage (di dalam try-catch)
    try {
        Artisan::call('storage:link');
        $results['storage_link'] = trim(Artisan::output());
    } catch (\Throwable $e) {
        $results['storage_link'] = $e->getMessage();
    }

    // 4. Bersihkan Cache Sistem (di dalam try-catch)
    try {
        Artisan::call('optimize:clear');
        $results['optimize_clear'] = trim(Artisan::output());
    } catch (\Throwable $e) {
        $results['optimize_clear'] = $e->getMessage();
    }

    return response()->json([
        'status'  => 'success',
        'message' => 'Database migration and deployment tasks executed successfully.',
        'details' => $results,
    ]);
});

// --------------------------------------------------------------------------
// AUTENTIKASI & DATA PUBLIK
// --------------------------------------------------------------------------
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/forgot-password/send-otp', [ForgotPasswordController::class, 'sendOtp'])
    ->middleware('throttle:5,1');
Route::post('/forgot-password/verify-otp', [ForgotPasswordController::class, 'verifyOtp'])
    ->middleware('throttle:10,1');
Route::post('/forgot-password/reset', [ForgotPasswordController::class, 'reset'])
    ->middleware('throttle:5,1');
Route::get('/calendar/events', [EventController::class, 'calendar']);
Route::get('/rooms', [RoomController::class, 'publicIndex']);
Route::get('/tv-display/agenda', [TvDisplayController::class, 'agenda']);

// --------------------------------------------------------------------------
// ENDPOINT TERPROTEKSI (SANCTUM)
// --------------------------------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('role:Admin,Waka,Guru,Organisasi,PIC')->group(function () {
        Route::post('/events', [EventController::class, 'store']);
        Route::get('/my/events', fn (Request $request) => response()->json([
            'data' => $request->user()->events()->with('room')->orderByDesc('date')->paginate(20),
        ]));
        Route::post('/complaints', [ComplaintController::class, 'store']);
    });

    Route::middleware('role:Admin,Waka,PIC,Guru,Organisasi')->get('/complaints', [ComplaintController::class, 'index']);
    Route::middleware('role:PIC')->patch('/complaints/{complaint}/status', [ComplaintController::class, 'updateStatus']);

    Route::get('/notifications', fn (Request $request) => response()->json([
        'data' => $request->user()->notifications()->latest()->paginate(20),
    ]));
    Route::post('/notifications/{notification}/read', function (Request $request, string $notification) {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        return response()->json(['message' => 'Notifikasi ditandai telah dibaca.']);
    });

    Route::middleware('role:Admin,Waka')->group(function () {
        Route::post('/events/{event}/move', [EventController::class, 'move']);
        Route::get('/admin/complaints/{complaint}/whatsapp', [ComplaintController::class, 'whatsapp']);

        Route::get('/admin/users', [UserController::class, 'index']);
        Route::post('/admin/users', [UserController::class, 'store']);
        Route::put('/admin/users/{user}', [UserController::class, 'update']);
        Route::delete('/admin/users/{user}', [UserController::class, 'destroy']);

        Route::get('/admin/rooms', [RoomController::class, 'index']);
        Route::post('/admin/rooms', [RoomController::class, 'store']);
        Route::put('/admin/rooms/{room}', [RoomController::class, 'update']);
        Route::delete('/admin/rooms/{room}', [RoomController::class, 'destroy']);
    });

    Route::middleware('role:Admin,Waka')->patch('/events/{event}/decision', [EventController::class, 'decide']);
});
