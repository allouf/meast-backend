<?php

namespace Modules\Wallet\Exceptions;

use Exception;

/**
 * Base class for all wallet-domain exceptions.
 *
 * Catch this when you want to handle ANY wallet-related failure;
 * catch a more specific subclass to handle one specific case.
 */
abstract class WalletException extends Exception
{
}
