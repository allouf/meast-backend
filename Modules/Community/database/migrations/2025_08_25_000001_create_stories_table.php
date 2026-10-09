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
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('country_code', 2)->nullable();
            $table->string('region')->nullable();
            $table->text('content')->nullable();
            $table->string('media')->nullable(); // For image/video
            $table->enum('status', ['active', 'inactive', 'draft'])->default('active');
            $table->string('background_color')->nullable();
            $table->enum('type', ['image', 'video', 'text'])->default('text');
            $table->timestamp('expires_at')->nullable(); // Stories typically expire after 24 hours
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
