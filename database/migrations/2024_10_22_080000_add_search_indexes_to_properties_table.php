<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds database indexes to optimize search performance
     * for property_name and location-based queries
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Add index on property_name for fast text searches
            // This dramatically improves LIKE queries on property names
            $table->index('property_name', 'idx_properties_name');
            
            // Add composite index for location-based filtering
            // Optimizes queries filtering by location_id and status together
            $table->index(['location_id', 'status'], 'idx_properties_location_status');
            
            // Add index on status for quick status-based filtering
            $table->index('status', 'idx_properties_status');
            
            // Add index on is_featured for featured property queries
            $table->index('is_featured', 'idx_properties_featured');
            
            // Add composite index for category filtering with status
            $table->index(['category_id', 'status'], 'idx_properties_category_status');
            
            // Add index on estimated_monthly for price range queries
            $table->index('estimated_monthly', 'idx_properties_price');
            
            // Add index on created_at for sorting by latest
            $table->index('created_at', 'idx_properties_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Drop all indexes in reverse order
            $table->dropIndex('idx_properties_created');
            $table->dropIndex('idx_properties_price');
            $table->dropIndex('idx_properties_category_status');
            $table->dropIndex('idx_properties_featured');
            $table->dropIndex('idx_properties_status');
            $table->dropIndex('idx_properties_location_status');
            $table->dropIndex('idx_properties_name');
        });
    }
};
