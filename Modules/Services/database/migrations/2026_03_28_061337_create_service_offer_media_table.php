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
        Schema::create('service_offer_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_offer_id')->constrained('service_offers')->cascadeOnDelete();
            $table->string('media_path');
            $table->enum('media_type', ['image', 'video']);

            $table->boolean('is_cover')->default(false);

            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->index(['service_offer_id', 'media_type']);
            $table->index(['service_offer_id', 'is_cover']);
            $table->index(['service_offer_id', 'sort_order']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_offer_media');
    }
};
