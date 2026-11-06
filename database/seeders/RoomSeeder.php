<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $picRoleId = DB::table('roles')->where('role_name', 'PIC')->value('id')
            ?? DB::table('roles')->insertGetId([
                'role_name' => 'PIC',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        $makePic = function (string $username, string $fullname, string $phone) use ($picRoleId, $now): int {
            $existingId = DB::table('users')->where('username', $username)->value('id');

            if ($existingId) {
                return (int) $existingId;
            }

            return DB::table('users')->insertGetId([
                'username' => $username,
                'password' => Hash::make('password123'),
                'fullname' => $fullname,
                'phone_number' => $phone,
                'role_id' => $picRoleId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        };

        $arif = $makePic('arif', 'Mas Arif', '081234567801');
        $haris = $makePic('haris', 'Pak Haris', '081234567802');
        $sarpras = $makePic('sarpras', 'PIC Sarpras', '081234567899');
        $picLab = $makePic('pic_lab', 'Petugas PIC Lab', '089876543210');

        $rooms = [
            [
                'room_name' => 'Ruang Rapat Wijaya Kusuma',
                'room_type' => 'ruang_rapat',
                'capacity' => 135,
                'location' => 'Gedung Rapat Lt. 2',
                'pic_id' => $arif,
            ],
            [
                'room_name' => 'Ruang Rapat DAK',
                'room_type' => 'ruang_rapat',
                'capacity' => 40,
                'location' => 'Gedung Rapat Lt. 1',
                'pic_id' => $haris,
            ],
            [
                'room_name' => 'Masjid Lantai Bawah',
                'room_type' => 'masjid',
                'capacity' => 200,
                'location' => 'Masjid Sekolah - Lantai Bawah',
                'pic_id' => $sarpras,
            ],
            [
                'room_name' => 'Masjid Lantai Atas',
                'room_type' => 'masjid',
                'capacity' => 200,
                'location' => 'Masjid Sekolah - Lantai Atas',
                'pic_id' => $sarpras,
            ],
        ];

        for ($number = 1; $number <= 21; $number++) {
            $rooms[] = [
                'room_name' => "Lab {$number}",
                'room_type' => 'laboratorium',
                'capacity' => 36,
                'location' => "Gedung Laboratorium - Lab {$number}",
                'pic_id' => $picLab,
            ];
        }

        for ($number = 1; $number <= 32; $number++) {
            $rooms[] = [
                'room_name' => "Ruang {$number}",
                'room_type' => 'kelas',
                'capacity' => 36,
                'location' => "Gedung Kelas - Ruang {$number}",
                'pic_id' => $sarpras,
            ];
        }

        foreach ($rooms as $roomData) {
            Room::updateOrCreate(
                ['room_name' => $roomData['room_name']],
                [
                    ...$roomData,
                    'is_active' => true,
                ]
            );
        }

        $legacyRoomNames = [
            'Aula Pertemuan Graha Utama',
            'Perpustakaan Pusat',
            'Lab Komputer',
            'Lab RPL',
            'Lab Multimedia',
            'Lab Jaringan & TKJ',
            'Lab Bahasa',
        ];

        $classroomNames = collect(range(1, 32))
            ->map(fn ($number) => "Ruang {$number}")
            ->all();

        Room::where('room_type', 'kelas')
            ->whereNotIn('room_name', $classroomNames)
            ->update(['is_active' => false]);

        Room::whereIn('room_name', [
            ...$legacyRoomNames,
            ...collect(range(1, 32))->map(fn ($number) => "Classroom {$number}")->all(),
        ])->update(['is_active' => false]);
    }
}
