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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            $table->enum('sender', ['user', 'bot']); // user = client, bot = AI
            $table->text('content');
            $table->json('metadata')->nullable(); // Store AI context, analysis data, etc.
            $table->boolean('is_read')->default(false);
            $table->timestamps();

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
        Schema::dropIfExists('messages');
    }
};
