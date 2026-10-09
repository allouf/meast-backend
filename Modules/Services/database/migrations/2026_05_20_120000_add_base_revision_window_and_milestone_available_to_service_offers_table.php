<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_offers', function (Blueprint $table) {
            $table->unsignedInteger('base_revision_window')->nullable()->after('base_number_of_revisions');
            $table->boolean('milestone_available')->default(false)->after('base_revision_window');
        });
    }

    public function down(): void
    {
        Schema::table('service_offers', function (Blueprint $table) {
            $table->dropColumn(['base_revision_window', 'milestone_available']);
        });
    }
};
