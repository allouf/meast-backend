<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Services\Enums\DeliveryModel;
use Modules\Services\Enums\DeliveryTimeUnit;
use Modules\Services\Enums\ServiceOfferStatus;
use Modules\Services\Enums\ServiceOfferType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_provider_id')->constrained('service_providers')->cascadeOnDelete();
            $table->foreignId('service_category_id')->constrained('service_categories')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('base_price', 12, 2)->nullable();
            $table->string('base_currency', 10)->nullable();
            $table->unsignedInteger('base_delivery_time_value')->nullable();
            $table->enum('base_delivery_time_unit', DeliveryTimeUnit::values())->nullable();
            $table->unsignedSmallInteger('base_number_of_revisions')->nullable();
            $table->enum('type', ServiceOfferType::values());
            $table->enum('delivery_model', DeliveryModel::values());
            $table->boolean('is_active')->default(true);
            $table->enum('status', ServiceOfferStatus::values())
                ->default(ServiceOfferStatus::Pending->value);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['service_provider_id', 'is_active']);
            $table->index('service_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_offers');
    }
};
