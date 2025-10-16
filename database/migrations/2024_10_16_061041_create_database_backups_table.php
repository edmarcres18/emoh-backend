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
        Schema::create('database_backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('unique_identifier', 32)->unique();
            $table->string('path');
            $table->unsignedBigInteger('file_size')->default(0); // in bytes
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed', 'in_trash'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('backup_date')->useCurrent();
            $table->timestamp('trashed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('status');
            $table->index('backup_date');
            $table->index('trashed_at');
            $table->index(['status', 'backup_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('database_backups');
    }
};
