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
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('social_telegram')->nullable()->after('social_linkedin');
            $table->string('social_viber')->nullable()->after('social_telegram');
            $table->string('social_whatsapp')->nullable()->after('social_viber');
            $table->string('phone_number')->nullable()->after('contact_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['social_telegram', 'social_viber', 'social_whatsapp', 'phone_number']);
        });
    }
};
