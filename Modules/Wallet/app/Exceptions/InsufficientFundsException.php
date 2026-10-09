<?php

namespace Modules\Wallet\Exceptions;

/**
 * Thrown when a debit or lock would drive the available balance negative.
 *
 * Includes the wallet id and the requested amount in the message so a caller
 * (or logger) can identify the conflict without re-querying.
 */
class InsufficientFundsException extends WalletException
{
    public function __construct(
        public readonly int $walletId,
        public readonly string $requestedAmount,
        public readonly string $availableBalance,
    ) {
        parent::__construct(
            sprintf(
                'Wallet #%d has insufficient available funds: requested %s, available %s.',
                $walletId,
                $requestedAmount,
                $availableBalance,
            ),
        );
    }
}
