<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_offer_available_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_offer_id')->constrained('service_offers')->cascadeOnDelete();
            $table->string('day', 20); // sunday, monday, ...
            $table->string('start_time', 20);
            $table->string('end_time', 20);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['service_offer_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_offer_available_slots');
    }
};
