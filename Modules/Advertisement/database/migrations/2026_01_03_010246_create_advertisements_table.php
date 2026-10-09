<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Advertisement\Enums\AdvertisementStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('advertisement_category_id')->constrained()->onDelete('cascade');
            $table->foreignId('advertisement_subcategory_id')->nullable()->constrained()->nullOnDelete();
            $table->string('country_code', 2)->nullable();
            $table->string('region')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('is_price_negotiable')->default(false);
            $table->boolean('is_free')->default(false);
            $table->enum('condition', ['new', 'used'])->default('used');
            $table->enum('status', array_column(AdvertisementStatus::cases(), 'value'))
                ->default(AdvertisementStatus::DRAFT->value);
            $table->enum('contact_method', ['chat', 'phone', 'both'])->default('both');
            $table->string('phone')->nullable();
            $table->boolean('show_phone')->default(false);
            $table->json('images')->nullable();
            $table->integer('views_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('renewed_at')->nullable();
            $table->json('attributes')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['advertisement_category_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
