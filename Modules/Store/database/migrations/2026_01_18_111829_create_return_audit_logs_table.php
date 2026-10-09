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
        Schema::create('return_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_request_id')->constrained('ticket_returns')->cascadeOnDelete();
            $table->string('action');
            $table->text('note')->nullable();      
            $table->foreignId('by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_audit_logs');
    }
};
