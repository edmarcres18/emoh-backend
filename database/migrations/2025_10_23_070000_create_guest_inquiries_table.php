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
        Schema::create('guest_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->enum('subject', ['rental', 'lease', 'general', 'support']);
            $table->text('message');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->enum('status', ['pending', 'read', 'replied', 'archived'])->default('pending');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('status');
            $table->index('created_at');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_inquiries');
    }
};
