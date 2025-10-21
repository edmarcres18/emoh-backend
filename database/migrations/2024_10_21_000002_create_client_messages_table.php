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
        Schema::create('client_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('client_conversations')->onDelete('cascade');
            $table->enum('sender', ['client', 'ai']); // client or ai
            $table->text('message');
            $table->json('metadata')->nullable(); // Store additional info like tokens, model used, etc.
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('conversation_id');
            $table->index('sender');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_messages');
    }
};
