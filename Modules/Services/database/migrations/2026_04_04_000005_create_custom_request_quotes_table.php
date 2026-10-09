<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Services\Enums\QuoteStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_request_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained('custom_requests')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('service_providers')->cascadeOnDelete();
            $table->decimal('proposed_budget', 10, 2);
            $table->date('estimated_delivery_date');
            $table->text('cover_letter_or_message');
            $table->enum('status', QuoteStatus::values())
                ->default(QuoteStatus::Pending->value);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_request_quotes');
    }
};
