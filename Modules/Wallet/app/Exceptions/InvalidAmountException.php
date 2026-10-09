<?php

namespace Modules\Wallet\Exceptions;

/**
 * Thrown when a mutation amount fails validation:
 *   - empty string
 *   - non-numeric string
 *   - zero or negative value
 *   - more than 8 fractional digits (cannot be persisted at full precision)
 */
class InvalidAmountException extends WalletException
{
    public function __construct(
        public readonly string $given,
        string $reason,
    ) {
        parent::__construct(
            sprintf('Invalid wallet amount %s: %s.', var_export($given, true), $reason),
        );
    }
}
