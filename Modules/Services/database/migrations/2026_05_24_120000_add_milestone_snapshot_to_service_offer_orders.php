<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Freeze the milestone-level header (title, description, delivery_model,
     * request_modification_days) onto the order at define time.
     *
     * The order already snapshots its phases into service_offer_order_phases,
     * but the milestone HEADER lived only on the reusable Milestone template —
     * which the provider can edit after the order is placed. Reading the header
     * (or totals) off the live template therefore shows the buyer values they
     * never agreed to. Totals stay derived from the phase rows, so only the
     * non-derivable header text is stored here.
     */
    public function up(): void
    {
        Schema::table('service_offer_orders', function (Blueprint $table) {
            $table->json('milestone_snapshot')->nullable()->after('requirements');
        });

        // Backfill already-placed milestone orders from their linked template —
        // the only source available for legacy rows (new orders are snapshotted
        // at define time). Portable chunked loop (no JOIN-UPDATE) so it runs on
        // both MySQL (UAT) and SQLite (local).
        DB::table('service_offer_orders')
            ->where('orderable_type', 'milestone_template')
            ->whereNotNull('orderable_id')
            ->whereNull('milestone_snapshot')
            ->orderBy('id')
            ->chunkById(200, function ($orders) {
                foreach ($orders as $order) {
                    $template = DB::table('milestones')->find($order->orderable_id);

                    if (! $template) {
                        continue;
                    }

                    DB::table('service_offer_orders')
                        ->where('id', $order->id)
                        ->update([
                            'milestone_snapshot' => json_encode([
                                'title' => $template->title,
                                'description' => $template->description,
                                'delivery_model' => $template->delivery_model,
                                'request_modification_days' => (int) $template->request_modification_days,
                            ]),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('service_offer_orders', function (Blueprint $table) {
            $table->dropColumn('milestone_snapshot');
        });
    }
};
