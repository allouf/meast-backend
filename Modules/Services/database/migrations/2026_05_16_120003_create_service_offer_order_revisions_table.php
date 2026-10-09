<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Services\Enums\ServiceOfferOrderRevisionRequestType;
use Modules\Services\Enums\ServiceOfferOrderRevisionStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_offer_order_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_offer_order_id')
                ->constrained('service_offer_orders')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('service_offer_order_phase_id')->nullable();
            $table->unsignedInteger('delivery_version');
            $table->text('notes');
            $table->enum('request_type', ServiceOfferOrderRevisionRequestType::values());
            $table->json('attachments')->nullable();
            $table->enum('status', ServiceOfferOrderRevisionStatus::values())
                ->default(ServiceOfferOrderRevisionStatus::Pending->value);
            $table->text('rejection_reason')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });

        Schema::table('service_offer_order_revisions', function (Blueprint $table) {
            $table->foreign('service_offer_order_phase_id', 'soo_revisions_phase_fk')
                ->references('id')
                ->on('service_offer_order_phases')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_offer_order_revisions');
    }
};
