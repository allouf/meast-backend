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
        Schema::create('sla_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('issue_type_id');
            $table->integer('sla_response_hours')->default(24);

            $table->timestamps();

            $table->foreign('store_id')
                ->references('id')->on('stores')
                ->onDelete('cascade');

            $table->foreign('issue_type_id')
                ->references('id')->on('issue_types')
                ->onDelete('cascade');
             });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sla_rules');
    }
};
