<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration {
    public function up(): void
    {
        $now = now();

        // 1. Dapatkan role PIC
        $picRoleId = DB::table('roles')->where('role_name', 'PIC')->value('id');
        if (!$picRoleId) {
            $picRoleId = DB::table('roles')->insertGetId([
                'role_name'  => 'PIC',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 2. Buat atau perbarui 4 akun PIC dengan kata sandi: helloworld1
        $passwordHash = Hash::make('helloworld1');

        $pics = [
            'lega' => [
                'fullname'     => 'Lega Danu Kumara',
                'phone_number' => '081234567811',
            ],
            'wahid' => [
                'fullname'     => 'Wahid Abduraman',
                'phone_number' => '081234567812',
            ],
            'heri' => [
                'fullname'     => 'Heri Prasetyo',
                'phone_number' => '081234567813',
            ],
            'arif' => [
                'fullname'     => 'Arif Kurniawan',
                'phone_number' => '081234567801',
            ],
        ];

        $picIds = [];
        foreach ($pics as $username => $data) {
            $existing = DB::table('users')->where('username', $username)->first();
            if ($existing) {
                DB::table('users')->where('id', $existing->id)->update([
                    'fullname'     => $data['fullname'],
                    'phone_number' => $data['phone_number'],
                    'password'     => $passwordHash,
                    'role_id'      => $picRoleId,
                    'updated_at'   => $now,
                ]);
                $picIds[$username] = (int) $existing->id;
            } else {
                $id = DB::table('users')->insertGetId([
                    'username'     => $username,
                    'password'     => $passwordHash,
                    'fullname'     => $data['fullname'],
                    'phone_number' => $data['phone_number'],
                    'role_id'      => $picRoleId,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
                $picIds[$username] = (int) $id;
            }
        }

        // 3. Pastikan Ruang Wijaya Kusuma & Lab 1 - 22 (tanpa Lab 15) ada
        $activeRoomNames = [
            'Ruang Wijaya Kusuma',
            'Lab 1', 'Lab 2', 'Lab 3', 'Lab 4', 'Lab 5', 'Lab 6',
            'Lab 7', 'Lab 8', 'Lab 9', 'Lab 10', 'Lab 11', 'Lab 12',
            'Lab 13', 'Lab 14', 'Lab 16', 'Lab 17', 'Lab 18', 'Lab 19',
            'Lab 20', 'Lab 21', 'Lab 22',
        ];

        // Pastikan Ruang Wijaya Kusuma ada (rename jika ada Ruang Rapat Wijaya Kusuma)
        $rwk = DB::table('rooms')->whereIn('room_name', ['Ruang Wijaya Kusuma', 'Ruang Rapat Wijaya Kusuma'])->first();
        if ($rwk) {
            DB::table('rooms')->where('id', $rwk->id)->update([
                'room_name' => 'Ruang Wijaya Kusuma',
                'room_type' => 'ruang_rapat',
                'pic_id'    => $picIds['arif'],
                'is_active' => true,
                'updated_at'=> $now,
            ]);
        } else {
            DB::table('rooms')->insert([
                'room_name' => 'Ruang Wijaya Kusuma',
                'room_type' => 'ruang_rapat',
                'capacity'  => 135,
                'location'  => 'Gedung Rapat Lt. 2',
                'pic_id'    => $picIds['arif'],
                'is_active' => true,
                'created_at'=> $now,
                'updated_at'=> $now,
            ]);
        }

        // Pastikan Lab 1 s/d 22 ada
        for ($num = 1; $num <= 22; $num++) {
            if ($num === 15) continue; // Lab 15 tidak ada di daftar
            $labName = "Lab {$num}";
            $existingLab = DB::table('rooms')->where('room_name', $labName)->first();
            if (!$existingLab) {
                DB::table('rooms')->insert([
                    'room_name' => $labName,
                    'room_type' => 'laboratorium',
                    'capacity'  => 36,
                    'location'  => "Gedung Laboratorium - {$labName}",
                    'pic_id'    => $picIds['heri'], // temporary default, akan di-assign spesifik di bawah
                    'is_active' => true,
                    'created_at'=> $now,
                    'updated_at'=> $now,
                ]);
            }
        }

        // 4. Pembagian Ruangan Masing-Masing PIC (Tanpa Tabrakan)
        // Lega: Lab 2, 4, 6, 8, 11, 14, 19
        $legaRooms = ['Lab 2', 'Lab 4', 'Lab 6', 'Lab 8', 'Lab 11', 'Lab 14', 'Lab 19'];
        DB::table('rooms')->whereIn('room_name', $legaRooms)->update([
            'pic_id'     => $picIds['lega'],
            'is_active'  => true,
            'updated_at' => $now,
        ]);

        // Wahid: Lab 3, 7, 9, 16, 17, 20, 21
        $wahidRooms = ['Lab 3', 'Lab 7', 'Lab 9', 'Lab 16', 'Lab 17', 'Lab 20', 'Lab 21'];
        DB::table('rooms')->whereIn('room_name', $wahidRooms)->update([
            'pic_id'     => $picIds['wahid'],
            'is_active'  => true,
            'updated_at' => $now,
        ]);

        // Heri: Lab 1, 5, 10, 12, 13, 18, 22
        $heriRooms = ['Lab 1', 'Lab 5', 'Lab 10', 'Lab 12', 'Lab 13', 'Lab 18', 'Lab 22'];
        DB::table('rooms')->whereIn('room_name', $heriRooms)->update([
            'pic_id'     => $picIds['heri'],
            'is_active'  => true,
            'updated_at' => $now,
        ]);

        // Mas Arif: Ruang Wijaya Kusuma
        DB::table('rooms')->where('room_name', 'Ruang Wijaya Kusuma')->update([
            'pic_id'     => $picIds['arif'],
            'is_active'  => true,
            'updated_at' => $now,
        ]);

        // 5. Nonaktifkan semua ruangan selain 22 ruangan di atas (hanya ruangan yang diminta yang aktif)
        DB::table('rooms')
            ->whereNotIn('room_name', $activeRoomNames)
            ->update([
                'is_active'  => false,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        // Reversible jika diperlukan
    }
};
