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
            $table->string('path');
            $table->bigInteger('size')->nullable(); // File size in bytes
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('trashed_at')->nullable(); // Soft delete for trash functionality
            $table->timestamps();
            
            $table->index('created_by');
            $table->index('trashed_at');
            $table->index('created_at');
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
