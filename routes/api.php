<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\TvDisplayController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::get('/calendar/events', [EventController::class, 'calendar']);
Route::get('/rooms', [RoomController::class, 'publicIndex']);
Route::get('/tv-display/agenda', [TvDisplayController::class, 'agenda']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('role:Admin,Guru,Organisasi')->group(function () {
        Route::post('/events', [EventController::class, 'store']);
        Route::get('/my/events', fn (Request $request) => response()->json([
            'data' => $request->user()->events()->with('room')->orderByDesc('date')->paginate(20),
        ]));
        Route::post('/complaints', [ComplaintController::class, 'store']);
    });

    Route::middleware('role:Admin,PIC,Guru,Organisasi')->get('/complaints', [ComplaintController::class, 'index']);
    Route::middleware('role:PIC')->patch('/complaints/{complaint}/status', [ComplaintController::class, 'updateStatus']);

    Route::get('/notifications', fn (Request $request) => response()->json([
        'data' => $request->user()->notifications()->latest()->paginate(20),
    ]));
    Route::post('/notifications/{notification}/read', function (Request $request, string $notification) {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        return response()->json(['message' => 'Notifikasi ditandai telah dibaca.']);
    });

    Route::middleware('role:Admin')->group(function () {
        Route::patch('/events/{event}/decision', [EventController::class, 'decide']);
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
});
