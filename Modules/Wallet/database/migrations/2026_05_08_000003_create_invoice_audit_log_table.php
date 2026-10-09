<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only audit log of every state change on an Invoice.
 *
 * Required for finance reconciliation, dispute defence, and any future
 * "show me the complete payment history of this order" UI. Application
 * code only INSERTs into this table — there is no UPDATE or DELETE path.
 *
 * Written from inside the Invoice's state-transition methods (markCompleted,
 * markFailed, markRefunded, markExpired, markCancelled), within the same
 * DB transaction as the status change itself, so the log is guaranteed to
 * be consistent with the invoice state.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_audit_log', function (Blueprint $table) {
            $table->id();

            // Subject of the audit entry. Cascade is fine — if an invoice
            // is hard-deleted (admin cleanup), the log goes with it. We
            // don't expect to hard-delete invoices in practice.
            $table->foreignId('invoice_id')
                ->constrained('invoices')
                ->cascadeOnDelete();

            // What kind of event this entry records. Strings (not enum)
            // so new event types can be added without ALTER TABLE.
            // Examples: 'created', 'status_changed', 'refunded',
            //           'rail_attached', 'metadata_updated'.
            $table->string('event_type', 64);

            // Status transition recorded by this entry. Both NULL for
            // events that aren't status changes (e.g. metadata_updated).
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();

            // Who caused the event:
            //   'system'   = our own code / scheduled jobs
            //   'webhook'  = a webhook handler (Stripe, etc.)
            //   'user'     = an end-user action
            //   'admin'    = a backoffice action
            $table->string('actor_type', 32);
            // Actor id when applicable (user_id for 'user' / 'admin'; null otherwise).
            $table->unsignedBigInteger('actor_id')->nullable();

            // Free-form additional context. For status_changed: the rail's
            // payload. For refunded: the refund row id. For metadata_updated:
            // the diff. Whatever is helpful for later forensic inspection.
            $table->json('payload')->nullable();

            // Single timestamp — no updated_at since rows are never modified.
            $table->timestamp('created_at')->useCurrent();

            // Most common access: "give me the timeline for this invoice".
            $table->index(['invoice_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_audit_log');
    }
};
