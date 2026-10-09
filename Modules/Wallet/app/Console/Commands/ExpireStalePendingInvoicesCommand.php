<?php

namespace Modules\Wallet\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Wallet\Enums\InvoiceStatus;
use Modules\Wallet\Models\Invoice;
use Throwable;

/**
 * Marks Pending invoices past their expires_at as Expired.
 *
 * Runs on a schedule (every 5 minutes via WalletServiceProvider). For each
 * invoice it transitions, the standard state-machine method
 * Invoice::markExpired() handles:
 *   - Status update
 *   - Audit log entry (event_type='status_changed', actor_type='system')
 *   - PaymentExpired event dispatch
 *
 * Why a fixed `--limit` (default 500): protects against runaway processing
 * if the table grows large or a deploy reintroduces a backlog. Five minutes
 * × 500 = 6,000/hour, far above any realistic invoice creation rate.
 *
 * Why we re-fetch each invoice inside the loop instead of iterating the
 * eager-loaded collection: every markExpired() runs in its own DB
 * transaction; if one fails (e.g. the invoice was just transitioned by a
 * webhook between our SELECT and the UPDATE), we recover by re-fetching
 * fresh state on the next iteration.
 */
class ExpireStalePendingInvoicesCommand extends Command
{
    protected $signature = 'wallet:expire-stale-invoices
                            {--limit=500 : Maximum invoices to process in one run}';

    protected $description = 'Mark pending invoices whose expires_at has passed as Expired.';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        // Fetch only the ids of candidates. We re-load each invoice fresh
        // inside the loop so we work against the latest DB state — guards
        // against a webhook racing us between SELECT and UPDATE.
        $ids = Invoice::query()
            ->where('status', InvoiceStatus::Pending->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        if ($ids->isEmpty()) {
            $this->info('No invoices to expire.');
            return self::SUCCESS;
        }

        $expired = 0;
        $skipped = 0;
        $errors  = 0;

        foreach ($ids as $id) {
            try {
                $invoice = Invoice::find($id);

                // Could be gone (cleanup) or already transitioned by a
                // webhook between our SELECT and now — either case: skip.
                if ($invoice === null || ! $invoice->isPending()) {
                    $skipped++;
                    continue;
                }

                // Re-check expiry — a webhook may have extended it.
                if ($invoice->expires_at === null || $invoice->expires_at->isFuture()) {
                    $skipped++;
                    continue;
                }

                $invoice->markExpired(actorType: 'system');
                $expired++;
            } catch (Throwable $e) {
                // Don't let one bad row halt the whole batch — log and
                // continue. The next cron run will retry this invoice.
                $errors++;
                Log::error('Invoice expiration failed', [
                    'invoice_id' => $id,
                    'error'      => $e->getMessage(),
                ]);
                $this->warn("Skipped invoice {$id}: {$e->getMessage()}");
            }
        }

        $this->info(sprintf(
            'Expired: %d   Skipped: %d   Errors: %d   (limit %d)',
            $expired, $skipped, $errors, $limit,
        ));

        return self::SUCCESS;
    }
}
