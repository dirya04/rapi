<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder {
    public function run(): void {
        // 1. Buat Roles
        $adminRole = Role::firstOrCreate(['role_name' => 'Admin']);
        $wakaRole = Role::firstOrCreate(['role_name' => 'Waka']);
        $picRole = Role::firstOrCreate(['role_name' => 'PIC']);
        $guruRole = Role::firstOrCreate(['role_name' => 'Guru']);
        $organisasiRole = Role::firstOrCreate(['role_name' => 'Organisasi']);

        // 2. Akun Waka / Admin
        User::updateOrCreate(['username' => 'admin'], [
            'fullname'     => 'Waka / Admin Sekolah',
            'phone_number' => '081234567890',
            'password'     => Hash::make('admin123'),
            'role_id'      => $adminRole->id,
        ]);

        User::updateOrCreate(['username' => 'waka'], [
            'fullname'     => 'Waka Sekolah',
            'phone_number' => '081234567892',
            'password'     => Hash::make(env('RAPI_WAKA_PASSWORD', 'waka12345')),
            'role_id'      => $wakaRole->id,
        ]);

        // 3. Akun Petugas PIC Ruang
        // Akun PIC Spesifik sesuai instruksi (kata sandi: helloworld1)
        User::updateOrCreate(['username' => 'lega'], [
            'fullname'     => 'Lega Danu Kumara',
            'phone_number' => '081234567811',
            'password'     => Hash::make('helloworld1'),
            'role_id'      => $picRole->id,
        ]);

        User::updateOrCreate(['username' => 'wahid'], [
            'fullname'     => 'Wahid Abduraman',
            'phone_number' => '081234567812',
            'password'     => Hash::make('helloworld1'),
            'role_id'      => $picRole->id,
        ]);

        User::updateOrCreate(['username' => 'heri'], [
            'fullname'     => 'Heri Prasetyo',
            'phone_number' => '081234567813',
            'password'     => Hash::make('helloworld1'),
            'role_id'      => $picRole->id,
        ]);

        User::updateOrCreate(['username' => 'arif'], [
            'fullname'     => 'Arif Kurniawan',
            'phone_number' => '081234567801',
            'password'     => Hash::make('helloworld1'),
            'role_id'      => $picRole->id,
        ]);

        // Akun PIC Umum / Bantuan
        User::updateOrCreate(['username' => 'pic_lab'], [
            'fullname'     => 'Petugas PIC Lab',
            'phone_number' => '089876543210',
            'password'     => Hash::make('pic123'),
            'role_id'      => $picRole->id,
        ]);

        User::updateOrCreate(['username' => 'sarpras'], [
            'fullname'     => 'PIC Sarpras Sekolah',
            'phone_number' => '081234567899',
            'password'     => Hash::make('sarpras123'),
            'role_id'      => $picRole->id,
        ]);

        // 4. Akun Guru
        User::updateOrCreate(['username' => 'guru_pembina'], [
            'fullname'     => 'Guru Pembina Organisasi',
            'phone_number' => '085123456789',
            'password'     => Hash::make('guru123'),
            'role_id'      => $guruRole->id,
        ]);

        // 5. Akun Organisasi
        User::updateOrCreate(['username' => 'osis'], [
            'fullname'     => 'Pengurus Organisasi Siswa',
            'phone_number' => '081234567891',
            'password'     => Hash::make('osis12345'),
            'role_id'      => $organisasiRole->id,
        ]);

        $this->call(RoomSeeder::class);
    }
}