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
                DB::table('users')->where('id', $existingId)->update([
                    'fullname' => $fullname,
                    'phone_number' => $phone,
                    'password' => Hash::make('helloworld1'),
                    'role_id' => $picRoleId,
                    'updated_at' => $now,
                ]);
                return (int) $existingId;
            }

            return DB::table('users')->insertGetId([
                'username' => $username,
                'password' => Hash::make('helloworld1'),
                'fullname' => $fullname,
                'phone_number' => $phone,
                'role_id' => $picRoleId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        };

        $lega = $makePic('lega', 'Lega Danu Kumara', '081234567811');
        $wahid = $makePic('wahid', 'Wahid Abduraman', '081234567812');
        $heri = $makePic('heri', 'Heri Prasetyo', '081234567813');
        $arif = $makePic('arif', 'Arif Kurniawan', '081234567801');

        $activeRooms = [
            [
                'room_name' => 'Ruang Wijaya Kusuma',
                'room_type' => 'ruang_rapat',
                'capacity' => 135,
                'location' => 'Gedung Rapat Lt. 2',
                'pic_id' => $arif,
            ],
        ];

        // Lega: Lab 2, 4, 6, 8, 11, 14, 19
        $legaLabs = [2, 4, 6, 8, 11, 14, 19];
        foreach ($legaLabs as $n) {
            $activeRooms[] = [
                'room_name' => "Lab {$n}",
                'room_type' => 'laboratorium',
                'capacity' => 36,
                'location' => "Gedung Laboratorium - Lab {$n}",
                'pic_id' => $lega,
            ];
        }

        // Wahid: Lab 3, 7, 9, 16, 17, 20, 21
        $wahidLabs = [3, 7, 9, 16, 17, 20, 21];
        foreach ($wahidLabs as $n) {
            $activeRooms[] = [
                'room_name' => "Lab {$n}",
                'room_type' => 'laboratorium',
                'capacity' => 36,
                'location' => "Gedung Laboratorium - Lab {$n}",
                'pic_id' => $wahid,
            ];
        }

        // Heri: Lab 1, 5, 10, 12, 13, 18, 22
        $heriLabs = [1, 5, 10, 12, 13, 18, 22];
        foreach ($heriLabs as $n) {
            $activeRooms[] = [
                'room_name' => "Lab {$n}",
                'room_type' => 'laboratorium',
                'capacity' => 36,
                'location' => "Gedung Laboratorium - Lab {$n}",
                'pic_id' => $heri,
            ];
        }

        $activeNames = [];
        foreach ($activeRooms as $roomData) {
            $activeNames[] = $roomData['room_name'];
            Room::updateOrCreate(
                ['room_name' => $roomData['room_name']],
                [
                    ...$roomData,
                    'is_active' => true,
                ]
            );
        }

        // Nonaktifkan semua ruangan selain 22 ruangan ini
        Room::whereNotIn('room_name', $activeNames)->update(['is_active' => false]);
    }
}
