<?php

namespace Modules\Wallet\Support;

use Modules\Wallet\Exceptions\InvalidAmountException;

/**
 * Validation and arithmetic helpers for wallet amounts.
 *
 * All wallet amounts are kept as strings end-to-end to preserve precision —
 * the wallet columns are decimal(24,8) and we never cast through float.
 * Use bcmath for any addition or subtraction; never use `+` or `-` operators
 * on these strings.
 *
 * Supported format: a non-empty decimal string with optional fractional
 * digits (max 8). Examples that pass: "1", "1.5", "0.00000001", "1234567.89".
 * Examples that fail: "" (empty), "abc" (non-numeric), "0" (zero),
 * "-1" (negative), "1.123456789" (>8 fractional digits).
 */
final class Amount
{
    /** Maximum number of digits after the decimal point we can store. */
    public const SCALE = 8;

    /** Cannot be instantiated — static helpers only. */
    private function __construct() {}

    /**
     * Validate an amount and return it in canonical form (no leading zeros,
     * no surrounding whitespace, exactly SCALE fractional digits).
     *
     * @throws InvalidAmountException if the input is malformed or non-positive
     */
    public static function normalize(string $amount): string
    {
        $trimmed = trim($amount);

        if ($trimmed === '') {
            throw new InvalidAmountException($amount, 'empty string');
        }

        if (! preg_match('/^\d+(\.\d{1,' . self::SCALE . '})?$/', $trimmed)) {
            throw new InvalidAmountException(
                $amount,
                'must be a non-negative decimal with at most ' . self::SCALE . ' fractional digits',
            );
        }

        // bccomp returns 0 if equal, -1 if first<second, 1 if first>second.
        if (bccomp($trimmed, '0', self::SCALE) <= 0) {
            throw new InvalidAmountException($amount, 'must be strictly positive (zero is not allowed)');
        }

        // Canonical form: pad fractional part to SCALE digits.
        return bcadd($trimmed, '0', self::SCALE);
    }

    /**
     * a + b, both validated decimal strings, result at SCALE precision.
     */
    public static function add(string $a, string $b): string
    {
        return bcadd($a, $b, self::SCALE);
    }

    /**
     * a - b, both validated decimal strings, result at SCALE precision.
     * The result may be negative — caller must check before persisting if
     * the column is unsigned.
     */
    public static function sub(string $a, string $b): string
    {
        return bcsub($a, $b, self::SCALE);
    }

    /**
     * Returns true if a >= b. Both must be valid decimal strings.
     */
    public static function gte(string $a, string $b): bool
    {
        return bccomp($a, $b, self::SCALE) >= 0;
    }
}
