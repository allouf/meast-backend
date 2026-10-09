<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-phase description was never copied from the template line into the
     * order's phase snapshot (createPhasesFromTemplate skipped it), so it was
     * only readable off the live template. Add the column so the phase row is a
     * complete snapshot, and backfill existing phases from their template line.
     */
    public function up(): void
    {
        Schema::table('service_offer_order_phases', function (Blueprint $table) {
            $table->text('description')->nullable()->after('service_name');
        });

        // Backfill from the milestone_services line each phase was cloned from.
        DB::table('service_offer_order_phases')
            ->whereNull('description')
            ->whereNotNull('milestone_service_id')
            ->orderBy('id')
            ->chunkById(200, function ($phases) {
                foreach ($phases as $phase) {
                    $description = DB::table('milestone_services')
                        ->where('id', $phase->milestone_service_id)
                        ->value('description');

                    if ($description !== null) {
                        DB::table('service_offer_order_phases')
                            ->where('id', $phase->id)
                            ->update(['description' => $description]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('service_offer_order_phases', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
