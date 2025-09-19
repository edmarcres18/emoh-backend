<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('email_verification_otp', 6)->nullable()->after('email_verified_at');
            $table->timestamp('otp_expires_at')->nullable()->after('email_verification_otp');
            $table->integer('otp_attempts')->default(0)->after('otp_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['email_verification_otp', 'otp_expires_at', 'otp_attempts']);
        });
    }
};
