<?php

namespace Modules\Wallet\Exceptions;

/**
 * Thrown when an unlock would drive the locked balance negative —
 * i.e. trying to release more than was ever locked.
 */
class InsufficientLockedFundsException extends WalletException
{
    public function __construct(
        public readonly int $walletId,
        public readonly string $requestedAmount,
        public readonly string $lockedBalance,
    ) {
        parent::__construct(
            sprintf(
                'Wallet #%d has insufficient locked funds to unlock: requested %s, locked %s.',
                $walletId,
                $requestedAmount,
                $lockedBalance,
            ),
        );
    }
}
