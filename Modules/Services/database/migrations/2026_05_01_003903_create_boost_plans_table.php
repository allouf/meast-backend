<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('boost_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 3 Days Boost
            $table->decimal('base_price', 10, 2);
            $table->string('currency', 3);

            $table->unsignedInteger('duration_days');

            $table->json('targeting');
            // example: ["category", "location"]

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boost_plans');
    }
};
