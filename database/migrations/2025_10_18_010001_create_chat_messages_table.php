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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->onDelete('cascade');
            $table->string('session_id', 100)->index(); // For anonymous users
            $table->enum('role', ['user', 'assistant', 'system'])->default('user');
            $table->text('message');
            $table->text('context')->nullable(); // Store query context/metadata
            $table->json('metadata')->nullable(); // Additional metadata (model, tokens, etc)
            $table->timestamps();
            
            // Index for efficient queries
            $table->index(['client_id', 'created_at']);
            $table->index(['session_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
