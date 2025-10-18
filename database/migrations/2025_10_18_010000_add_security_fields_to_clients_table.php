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
            // Failed login tracking
            $table->integer('failed_login_attempts')->default(0)->after('password');
            $table->timestamp('last_failed_login_at')->nullable()->after('failed_login_attempts');
            $table->timestamp('locked_until')->nullable()->after('last_failed_login_at');
            
            // Security tracking
            $table->string('last_login_ip', 45)->nullable()->after('locked_until');
            $table->string('browser_fingerprint', 100)->nullable()->after('last_login_ip');
            $table->timestamp('last_successful_login_at')->nullable()->after('browser_fingerprint');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'failed_login_attempts',
                'last_failed_login_at',
                'locked_until',
                'last_login_ip',
                'browser_fingerprint',
                'last_successful_login_at',
            ]);
        });
    }
};
