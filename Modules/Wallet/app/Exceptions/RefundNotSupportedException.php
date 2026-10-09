<?php

namespace Modules\Wallet\Exceptions;

use RuntimeException;

/**
 * Thrown when a rail receives a refund() call but doesn't support
 * programmatic refunds.
 *
 * NowPaymentsRail throws this because crypto refunds are irreversible
 * on-chain and must be issued manually from the merchant payout wallet.
 * Callers (admin tools, support flows) catch this and route to the
 * out-of-band manual refund process — the rail records nothing.
 */
class RefundNotSupportedException extends RuntimeException
{
}
