<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Switch status from ENUM('active') to VARCHAR(20) to allow values like
        // active, pending, expired, terminated, ended
        DB::statement("ALTER TABLE rented MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active'");

        // Ensure an index exists on (status)
        Schema::table('rented', function (Blueprint $table) {
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to ENUM('active')
        DB::statement("ALTER TABLE rented MODIFY COLUMN status ENUM('active') NOT NULL DEFAULT 'active'");

        Schema::table('rented', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};
