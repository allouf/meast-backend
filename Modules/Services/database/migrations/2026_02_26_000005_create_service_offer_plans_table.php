<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Services\Enums\DeliveryTimeUnit;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_offer_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_offer_id')->constrained('service_offers')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('currency', 10);
            $table->unsignedInteger('delivery_time_value');
            $table->enum('delivery_time_unit', DeliveryTimeUnit::values());
            $table->string('revision_window', 100)->nullable();
            $table->unsignedSmallInteger('number_of_revisions')->nullable();
            $table->string('revision_scope_rules', 2000)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('service_offer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_offer_plans');
    }
};
