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
        Schema::create('community_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('community_status')->default(true);             
            $table->unsignedInteger('max_post_length')->default(500);
            $table->unsignedSmallInteger('reels_duration_seconds')->default(30);
            $table->unsignedSmallInteger('interaction_window_hours')->default(24);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_settings');
    }
};
