<?php

namespace Modules\Wallet\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Wallet\Enums\WalletTransactionType;
use Modules\Wallet\Http\Resources\User\WalletResource;
use Modules\Wallet\Http\Resources\User\WalletTransactionResource;
use Modules\Wallet\Services\WalletService;

/**
 * User-facing read endpoints for the platform-services wallet.
 *
 * Both endpoints are scoped to the authenticated user — the wallet ID is
 * derived from Auth::id(); clients can never query another user's wallet.
 */
class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService,
    ) {
    }

    /**
     * GET /api/v1/wallet
     *
     * Returns the authenticated user's wallet (creates it on first call).
     */
    public function show()
    {
        try {
            $userId = Auth::id();
            $wallet = $this->walletService->getOrCreate($userId);

            return $this->successResponse(__('wallet::messages.fetch_success'), new WalletResource($wallet));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * GET /api/v1/wallet/transactions
     *
     * Paginated list of the authenticated user's wallet transactions,
     * newest first. Accepts ?per_page=N (default 15, max 100).
     */
    public function transactions(Request $request)
    {
        try {
            $userId  = Auth::id();
            $perPage = (int) $request->query('per_page', 15);
            // Clamp to a sane range — protects the DB from "?per_page=999999".
            $perPage = max(1, min($perPage, 100));

            // Optional ?type=CREDIT|DEBIT|LOCK|UNLOCK|REFUND filter. Anything
            // outside the enum is silently treated as no filter — keeps the
            // FE chip UI from being able to break the request with a typo.
            $type = $request->query('type');
            if ($type !== null && ! in_array($type, WalletTransactionType::values(), true)) {
                $type = null;
            }

            $page = $this->walletService->getTransactionsPaginated($userId, $perPage, $type);

            return $this->successResponse(
                __('wallet::messages.fetch_success'),
                [
                    'data' => WalletTransactionResource::collection($page->items()),
                    'meta' => [
                        'current_page' => $page->currentPage(),
                        'per_page'     => $page->perPage(),
                        'total'        => $page->total(),
                        'last_page'    => $page->lastPage(),
                    ],
                ],
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
