<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_offer_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_offer_id')->constrained('service_offers')->cascadeOnDelete();
            $table->string('question', 1000);
            $table->text('answer');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('service_offer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_offer_faqs');
    }
};
