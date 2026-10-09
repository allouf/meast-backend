<?php

namespace Modules\Wallet\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Wallet\Services\TransakWebhookService;
use RuntimeException;
use Throwable;

/**
 * Receives Transak webhook events.
 *
 * NO authentication: Transak doesn't have credentials to log in. Instead,
 * each request is signed via the 'transak-signature' header (HMAC-SHA256
 * of the raw body using our secret API key). The TransakWebhookService
 * verifies that signature before any processing.
 *
 * We pass the RAW body — not $request->all() re-encoded — because the
 * signature is computed over the bytes Transak sent, and re-serializing
 * the JSON would change whitespace/key ordering and break verification.
 *
 * Status codes:
 *   200  on success
 *   400  on invalid signature  (Transak should NOT retry)
 *   500  on transient processing failure (Transak SHOULD retry)
 */
class TransakWebhookController extends Controller
{
    public function __construct(
        protected TransakWebhookService $webhookService,
    ) {
    }

    public function handle(Request $request)
    {
        $signature = (string) $request->header('transak-signature', '');

        if ($signature === '') {
            return response()->json(
                ['status' => false, 'message' => 'Missing transak-signature header'],
                400,
            );
        }

        try {
            $this->webhookService->handle(
                rawPayload:      $request->getContent(),
                signatureHeader: $signature,
            );

            return response()->json(['status' => true], 200);
        } catch (RuntimeException $e) {
            // Signature mismatch lands here — log and return 400 so
            // Transak doesn't retry an unauthorized request.
            Log::warning('Transak webhook rejected', [
                'reason' => $e->getMessage(),
            ]);
            return response()->json(
                ['status' => false, 'message' => 'Invalid signature'],
                400,
            );
        } catch (Throwable $e) {
            // Unexpected (DB/connection) failure — return 500 so Transak
            // retries the delivery on its schedule.
            Log::error('Transak webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(
                ['status' => false, 'message' => 'Internal error'],
                500,
            );
        }
    }
}
