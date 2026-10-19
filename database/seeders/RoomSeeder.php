<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        // Hindari data ganda kalau seeder dijalankan lebih dari sekali
        if (DB::table('rooms')->count() > 0) {
            return;
        }

        $now = now();

        // ---- Role PIC (pakai yang sudah ada, kalau belum ada dibuat) ----
        $picRoleId = DB::table('roles')->where('role_name', 'PIC')->value('id')
            ?? DB::table('roles')->insertGetId([
                'role_name' => 'PIC', 'created_at' => $now, 'updated_at' => $now,
            ]);

        // ---- Akun PIC (pakai yang sudah ada, kalau belum ada dibuat) ----
        $makePic = function (string $username, string $fullname) use ($picRoleId, $now) {
            return DB::table('users')->where('username', $username)->value('id')
                ?? DB::table('users')->insertGetId([
                    'username'     => $username,
                    'password'     => Hash::make('password123'), // segera ganti
                    'fullname'     => $fullname,
                    'phone_number' => '080000000000',            // isi nomor asli
                    'role_id'      => $picRoleId,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
        };

        $arif   = $makePic('arif', 'Mas Arif');
        $haris  = $makePic('haris', 'Pak Haris');
        $sarpras = $makePic('sarpras', 'PIC Sarpras');

        // ---- Data ruangan ----
        $rooms = [
            ['room_name' => 'Ruang Rapat Wijaya Kusuma', 'room_type' => 'ruang_rapat', 'capacity' => 135, 'location' => 'Gedung Rapat', 'pic_id' => $arif],
            ['room_name' => 'Ruang Rapat DAK',           'room_type' => 'ruang_rapat', 'capacity' => 40,  'location' => 'Gedung Rapat', 'pic_id' => $haris],
            // Kapasitas masjid belum diketahui, isi sesuai data Sarpras
            ['room_name' => 'Masjid Lantai Bawah',       'room_type' => 'ibadah', 'capacity' => 0,   'location' => 'Masjid',       'pic_id' => $sarpras],
            ['room_name' => 'Masjid Lantai Atas',        'room_type' => 'ibadah', 'capacity' => 0,   'location' => 'Masjid',       'pic_id' => $sarpras],
        ];

        // 21 laboratorium, masing-masing 36 kapasitas
        for ($i = 1; $i <= 21; $i++) {
            $rooms[] = ['room_name' => "Laboratorium $i", 'room_type' => 'laboratorium', 'capacity' => 36, 'location' => 'Gedung Laboratorium', 'pic_id' => $sarpras];
        }

        // 32 ruang kelas, masing-masing 36 kapasitas (hanya bisa dipinjam Sabtu-Minggu)
        for ($i = 1; $i <= 32; $i++) {
            $rooms[] = ['room_name' => "Ruang Kelas $i", 'room_type' => 'kelas', 'capacity' => 36, 'location' => 'Gedung Kelas', 'pic_id' => $sarpras];
        }

        $rooms = array_map(fn ($r) => $r + [
            'room_type' => 'lainnya',
            'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ], $rooms);

        DB::table('rooms')->insert($rooms);
    }
}