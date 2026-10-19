<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('events')->whereNull('requester_id')->update([
            'requester_id' => DB::raw('pic_id'),
        ]);

        if (DB::table('events')->whereNull('requester_id')->exists()) {
            throw new RuntimeException('Cannot require events.requester_id: legacy events have no requester.');
        }

        Schema::table('events', function (Blueprint $table) {
            $table->unsignedBigInteger('requester_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedBigInteger('requester_id')->nullable()->change();
        });
    }
};