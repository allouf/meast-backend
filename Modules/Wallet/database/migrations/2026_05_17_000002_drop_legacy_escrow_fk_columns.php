<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Legacy escrow FK columns were never created on fresh installs.
 */
return new class extends Migration
{
    public function up(): void
    {
        //
    }

    public function down(): void
    {
        //
    }
};
