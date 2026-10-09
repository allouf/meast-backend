<?php

namespace Modules\Wallet\Exceptions;

/**
 * Thrown when PaymentInitiator::initiate() can't produce a usable Invoice.
 *
 * Common causes:
 *   - Validation failure (bad amount, unknown kind, etc.).
 *   - No rail can handle the requested combination (no rail returned true
 *     from supports()).
 *   - The resolved rail's provider rejected the request (Stripe API error,
 *     malformed Connect account, etc.).
 *
 * Carries an HTTP-friendly status code so the BaseResponse trait surfaces
 * the right code to the API caller.
 */
class PaymentInitiationException extends \RuntimeException
{
    public function getStatusCode(): int
    {
        // 422 = the request itself was understood but couldn't be processed.
        return 422;
    }
}
