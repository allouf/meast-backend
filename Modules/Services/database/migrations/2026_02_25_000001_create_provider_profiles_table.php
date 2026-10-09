<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Services\Enums\ProviderVerificationStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('display_name');
            $table->string('logo')->nullable();
            $table->string('country_code', 10)->nullable();
            $table->string('region')->nullable();
            $table->text('bio')->nullable();
            $table->string('cover')->nullable();
            $table->string('identity_document')->nullable();
            $table->text('verification_notes')->nullable();
            $table->enum('verification_status', ProviderVerificationStatus::values())
                ->default(ProviderVerificationStatus::None->value);
            $table->boolean('is_verified')->default(false);
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->timestamps();

            $table->unique('user_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_providers');
    }
};
