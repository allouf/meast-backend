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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone')->unique()->nullable();
            $table->string('password')->nullable();
            $table->string('photo')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('region')->nullable();

            $table->enum('provider', ['email', 'phone', 'google', 'apple'])->default('email');
            $table->string('provider_id')->nullable();
            $table->string('provider_token')->nullable();
            $table->string('provider_refresh_token')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('ref_code')->unique()->nullable();
            $table->foreignId('referred_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_profile_complete')->default(true);
            $table->boolean('is_beta_user')->default(false);
            $table->string('deletion_reason')->nullable();
            $table->timestamps();
        });
        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->string('contact'); // phone or email
            $table->enum('type', ['email', 'phone']);
            $table->string('code');
            $table->timestamp('expires_at');
            $table->index(['contact', 'type']);
        });


        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
