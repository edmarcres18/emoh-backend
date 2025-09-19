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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->json('images')->nullable();
            $table->string('property_name');
            $table->decimal('estimated_monthly', 10, 2)->nullable();
            $table->decimal('lot_area', 8, 2)->nullable();
            $table->decimal('floor_area', 8, 2)->nullable();
            $table->text('details')->nullable()->comment('for lease');
            $table->enum('status', ['Renovation', 'Rented', 'Available'])->default('Available');
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
