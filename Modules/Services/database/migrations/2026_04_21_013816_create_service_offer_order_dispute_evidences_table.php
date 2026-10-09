<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Services\Enums\ServiceOfferOrderDisputeEvidenceType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_offer_order_dispute_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')
                ->constrained('service_offer_order_disputes')
                ->cascadeOnDelete();

                $table->enum('type', ServiceOfferOrderDisputeEvidenceType::values());


            $table->unsignedBigInteger('reference_id')->nullable();

            $table->json('data_snapshot');

            $table->timestamps();

            /*
        |----------------------------------------------------------
        | Indexes
        |----------------------------------------------------------
        */
            $table->index(['dispute_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_offer_order_dispute_evidences');
    }
};
