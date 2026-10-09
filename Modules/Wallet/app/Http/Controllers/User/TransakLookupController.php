<?php

namespace Modules\Wallet\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Exception;
use Modules\Wallet\Services\TransakLookupService;

/**
 * GET endpoints exposing Transak's catalog data to the platform's frontend.
 *
 * All three are thin wrappers around the cached service — the frontend
 * calls these instead of going to Transak directly so we control the
 * cache and shape of the response.
 *
 * These are user-facing (auth:api) but contain no per-user state.
 */
class TransakLookupController extends Controller
{
    public function __construct(
        protected TransakLookupService $lookup,
    ) {
    }

    /** GET /api/v1/transak/countries */
    public function countries()
    {
        try {
            return $this->successResponse(
                __('wallet::messages.fetch_success'),
                $this->lookup->getCountries(),
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /** GET /api/v1/transak/crypto-currencies */
    public function cryptoCurrencies()
    {
        try {
            return $this->successResponse(
                __('wallet::messages.fetch_success'),
                $this->lookup->getCryptoCurrencies(),
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /** GET /api/v1/transak/fiat-currencies */
    public function fiatCurrencies()
    {
        try {
            return $this->successResponse(
                __('wallet::messages.fetch_success'),
                $this->lookup->getFiatCurrencies(),
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
