<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->unique()->after('username');
            $table->string('otp_code', 10)->nullable()->after('phone_number');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
            $table->timestamp('phone_verified_at')->nullable()->after('otp_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email', 'otp_code', 'otp_expires_at', 'phone_verified_at']);
        });
    }
};
