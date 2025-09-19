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
        // Check if we're using SQLite or MySQL/other databases
        if (DB::getDriverName() === 'sqlite') {
            // For SQLite, we need to recreate the table since it doesn't support MODIFY COLUMN
            Schema::table('rented', function (Blueprint $table) {
                $table->string('status', 20)->default('active')->change();
            });
        } else {
            // For MySQL and other databases, use raw SQL
            DB::statement("ALTER TABLE rented MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active'");
        }

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
        // Check if we're using SQLite or MySQL/other databases
        if (DB::getDriverName() === 'sqlite') {
            // For SQLite, revert back to string with default
            Schema::table('rented', function (Blueprint $table) {
                $table->string('status')->default('active')->change();
            });
        } else {
            // For MySQL and other databases, revert back to ENUM('active')
            DB::statement("ALTER TABLE rented MODIFY COLUMN status ENUM('active') NOT NULL DEFAULT 'active'");
        }

        Schema::table('rented', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};
