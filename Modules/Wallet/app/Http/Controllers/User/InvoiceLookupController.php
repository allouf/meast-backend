<?php

namespace Modules\Wallet\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Services\Models\ServiceOfferOrderPhase;
use Modules\Wallet\Http\Resources\User\InvoiceResource;
use Modules\Wallet\Models\Invoice;

/**
 * Buyer-side lookup so the post-checkout return page can resolve a
 * Stripe Checkout `session_id` back to the invoice it belongs to and
 * the order (or phase) the buyer should land on.
 *
 * Stripe substitutes `{CHECKOUT_SESSION_ID}` into our success_url,
 * so the FE return page knows the session id but nothing else. This
 * endpoint translates that to a kind + order_id (+ phase_id when
 * relevant), letting the FE route the buyer to the right detail
 * page without baking domain routing decisions into the BE.
 */
class InvoiceLookupController extends Controller
{
    /**
     * GET /api/v1/invoices/by-session/{sessionId}
     *
     * Returns the invoice + resolved order/phase ids. 404 if the
     * session isn't recognised OR doesn't belong to the caller —
     * the two cases are deliberately indistinguishable so we don't
     * leak whether a given session id exists for someone else.
     */
    public function bySession(string $sessionId)
    {
        $invoice = Invoice::query()
            ->where('partner_reference_id', $sessionId)
            ->where('user_id', Auth::id())
            ->first();

        if ($invoice === null) {
            return $this->errorResponse(
                __('wallet::messages.invoice_not_found'),
                404,
            );
        }

        return $this->successResponse(
            __('wallet::messages.fetch_success'),
            [
                'invoice'  => new InvoiceResource($invoice),
                'kind'     => $invoice->kind,
                'order_id' => $this->resolveOrderId($invoice),
                'phase_id' => $invoice->kind === 'service_order_phase'
                    ? $invoice->referenceable_id
                    : null,
            ],
        );
    }

    /**
     * For service_order kind the referenceable IS the order — id is
     * the order_id. For service_order_phase the referenceable is the
     * phase, so we resolve the order via the phase relation. Other
     * kinds (wallet_topup, future) return null.
     */
    private function resolveOrderId(Invoice $invoice): ?int
    {
        return match ($invoice->kind) {
            'service_order'       => $invoice->referenceable_id,
            'service_order_phase' => ServiceOfferOrderPhase::query()
                ->whereKey($invoice->referenceable_id)
                ->value('service_offer_order_id'),
            default => null,
        };
    }
}
