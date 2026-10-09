<?php

namespace Modules\Wallet\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Exception;
use Modules\Wallet\Http\Requests\User\TransakQuoteRequest;
use Modules\Wallet\Services\TransakLookupService;

/**
 * GET /api/v1/transak/quote
 *
 * Returns a live BUY/SELL quote from Transak so the frontend can display
 * "you'll pay $X to receive Y MEEC" before the user opens the widget.
 *
 * The quote is NOT cached — Transak refreshes pricing every minute and
 * returns a quoteId that's only valid for ~60 seconds. The frontend
 * should re-fetch on stale or before opening the widget.
 */
class TransakQuoteController extends Controller
{
    public function __construct(
        protected TransakLookupService $lookup,
    ) {
    }

    public function quote(TransakQuoteRequest $request)
    {
        try {
            $quote = $this->lookup->getQuote($request->validated());

            return $this->successResponse(
                __('wallet::messages.fetch_success'),
                $quote,
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
