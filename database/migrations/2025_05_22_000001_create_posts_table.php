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
            Schema::create('posts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('country_code', 2)->nullable();
                $table->string('region')->nullable();
                $table->text('content');
                $table->string('image')->nullable();
                $table->enum('status', ['active', 'inactive', 'draft'])->default('active');
                $table->boolean('is_featured')->default(false);
                $table->enum('type', ['image', 'video', 'text', 'reel']);
                $table->timestamps();
                $table->index(['user_id', 'status']);
                $table->index(['status', 'created_at']);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
