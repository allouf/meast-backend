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
        Schema::create('suggested_content_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('content_type', ['ads', 'posts', 'reels', 'videos']);
            $table->unsignedTinyInteger('percentage')->default(0); 
            $table->unsignedTinyInteger('priority')->default(1); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suggested_content_settings');
    }
};
