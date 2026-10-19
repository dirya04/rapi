<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('requester_id')->nullable()->after('room_id')
                ->constrained('users')->restrictOnDelete();
            $table->index(['room_id', 'date', 'status', 'start_time', 'end_time'], 'events_room_schedule_idx');
        });

        DB::table('events')->whereNull('requester_id')->update([
            'requester_id' => DB::raw('pic_id'),
        ]);

        Schema::table('complaints', function (Blueprint $table) {
            $table->index(['room_id', 'status_repair', 'reported_at'], 'complaints_room_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropIndex('complaints_room_status_idx');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_room_schedule_idx');
            $table->dropConstrainedForeignId('requester_id');
        });
    }
};
