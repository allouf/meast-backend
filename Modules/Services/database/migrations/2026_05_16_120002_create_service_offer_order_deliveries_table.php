<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_offer_order_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_offer_order_id')
                ->constrained('service_offer_orders')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('service_offer_order_phase_id')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->text('message')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['service_offer_order_id', 'version'], 'uniq_order_version');
            $table->index('service_offer_order_id', 'idx_delivery_order');
        });

        Schema::table('service_offer_order_deliveries', function (Blueprint $table) {
            $table->foreign('service_offer_order_phase_id', 'soo_deliveries_phase_fk')
                ->references('id')
                ->on('service_offer_order_phases')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_offer_order_deliveries');
    }
};
