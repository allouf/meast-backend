<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Services\Enums\BoostCampaignStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('boost_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('service_providers')->cascadeOnDelete();
            $table->foreignId('boost_plan_id')->constrained();

            $table->dateTime('start_at');
            $table->dateTime('end_at');

            $table->enum('status', BoostCampaignStatus::values())
                ->default(BoostCampaignStatus::Pending->value);


            $table->json('targeting_scope');

            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);

            $table->timestamp('terminated_at')->nullable();


            $table->timestamps();

            $table->index(['service_offer_id', 'status']);
            $table->index(['start_at', 'end_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boost_campaigns');
    }
};
