<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('room_type', 30)->default('lainnya')->after('room_name');
        });

        DB::table('rooms')->where('room_name', 'like', 'Ruang Kelas %')->update(['room_type' => 'kelas']);
        DB::table('rooms')->where('room_name', 'like', 'Laboratorium %')->update(['room_type' => 'laboratorium']);
        DB::table('rooms')->where('room_name', 'like', 'Ruang Rapat %')->update(['room_type' => 'ruang_rapat']);
        DB::table('rooms')->where('room_name', 'like', 'Masjid %')->update(['room_type' => 'ibadah']);
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('room_type');
        });
    }
};