<?php

use Modules\Store\Enums\ProductStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->text('description')->nullable();
            $table->text('specifications')->nullable();
            $table->json('additional_features')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('price_before_discount', 10, 2)->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->enum('discount_type', ['percentage', 'fixed'])->nullable();
            $table->integer('number_of_buys')->default(0);
            $table->float('avg_rating')->default(0);
            $table->float('rating_count')->default(0);
            $table->enum('status', array_column(ProductStatus::cases(), 'value'))
                ->default(ProductStatus::ACTIVE->value);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE products MODIFY COLUMN additional_features JSON NULL AFTER specifications');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
