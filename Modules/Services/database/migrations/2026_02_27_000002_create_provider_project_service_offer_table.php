<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_project_service_offer', function (Blueprint $table) {
            $table->foreignId('provider_project_id')->constrained('provider_projects')->cascadeOnDelete();
            $table->foreignId('service_offer_id')->constrained('service_offers')->cascadeOnDelete();
            $table->primary(['provider_project_id', 'service_offer_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_project_service_offer');
    }
};
