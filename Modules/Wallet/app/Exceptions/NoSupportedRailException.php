<?php

namespace Modules\Wallet\Exceptions;

/**
 * Thrown by RailResolver when no registered rail returns supports()=true
 * for the given InvoiceContext.
 *
 * In practice this should be rare — every kind has at least one viable
 * rail. The most likely real cause is a marketplace flow being attempted
 * before StripeConnect (or equivalent) is registered, or the buyer's
 * platform credit being insufficient AND no fallback rail enabled.
 */
class NoSupportedRailException extends PaymentInitiationException
{
}
