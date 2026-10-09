<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Services\Enums\CustomRequestDeliveryModel;
use Modules\Services\Enums\CustomRequestStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('custom_request_categories')->cascadeOnDelete();
            $table->string('reference_number')->unique();
            $table->string('title');
            $table->text('description');
            $table->enum('delivery_model', CustomRequestDeliveryModel::values());
            $table->timestamp('deadline_date')->nullable();
            $table->decimal('budget_min', 10, 2)->nullable();
            $table->decimal('budget_max', 10, 2)->nullable();
            $table->enum('status', CustomRequestStatus::values())
                ->default(CustomRequestStatus::Open->value);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_requests');
    }
};
