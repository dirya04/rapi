<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // 1. Tabel Roles (Hak Akses)
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('role_name'); // Admin, PIC, Guru
            $table->timestamps();
        });

        // 2. Tabel Users (Pengguna)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('fullname');
            $table->string('phone_number');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->timestamps();
        });

        // 3. Tabel Rooms (Ruangan & PIC)
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_name');
            $table->integer('capacity');
            $table->string('location');
            $table->foreignId('pic_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Tabel Events (Peminjaman Ruang & Agenda)
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->foreignId('pic_id')->constrained('users')->onDelete('cascade'); // Peminjam
            $table->string('event_name');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['pending', 'approved', 'rejected', 'moved'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->text('move_reason')->nullable(); // Alasan jika dipindah Admin
            $table->timestamps();
        });

        // 5. Tabel Complaints (Pengaduan Kerusakan Facilities)
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->foreignId('reporter_id')->constrained('users')->onDelete('cascade');
            $table->text('description');
            $table->string('photo_path');
            $table->enum('status_repair', ['Belum Diperbaiki', 'Menunggu Spare Part', 'Sudah Diperbaiki'])->default('Belum Diperbaiki');
            $table->foreignId('handler_id')->nullable()->constrained('users');
            $table->timestamp('reported_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('events');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
    }
};