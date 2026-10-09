<?php

namespace Modules\Wallet\Repositories\Interfaces;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletTransaction;

/**
 * Contract for wallet persistence.
 *
 * Identity / read methods (Feature 1):
 *   - findByUserId
 *   - getOrCreateForUser
 *
 * Balance-mutating methods (Feature 2):
 *   - mutate (the single primitive that all four operations use)
 *
 * The high-level credit / debit / lock / unlock semantics live in
 * WalletService — this interface deals only with the persistence primitive.
 */
interface WalletRepositoryInterface
{
    /**
     * Return the wallet for the given user, or null if it doesn't exist.
     */
    public function findByUserId(int $userId): ?Wallet;

    /**
     * Return the wallet for the given user, creating it (with zero balances
     * and the given default currency) if it doesn't already exist.
     */
    public function getOrCreateForUser(int $userId, string $currency): Wallet;

    /**
     * Atomically mutate a wallet's balances and write one audit row.
     *
     * The repository handles:
     *   1. Opening a DB transaction.
     *   2. SELECT ... FOR UPDATE on the wallet row (concurrency safety).
     *   3. Calling the given $apply closure with the locked Wallet instance.
     *      The closure is responsible for computing and assigning new
     *      balance values (e.g. $wallet->available_balance = ...). It must
     *      also throw a domain exception if the operation isn't valid given
     *      the current balances (insufficient funds, etc.).
     *   4. Persisting the wallet row.
     *   5. Inserting a WalletTransaction audit row with the supplied
     *      type / amount / metadata, plus balance_before and balance_after
     *      derived from the affected balance bucket (which the closure
     *      reports via its return value — see Closure signature below).
     *   6. Committing.
     *
     * @param int     $walletId Wallet to mutate.
     * @param Closure $apply    Receives Wallet, returns array{
     *                              balance_before: string,
     *                              balance_after:  string,
     *                              type:           string,
     *                              amount:         string,
     *                              reference_type: ?string,
     *                              reference_id:   ?int,
     *                              metadata:       ?array,
     *                          }
     *                          The closure may throw domain exceptions; they
     *                          will propagate after the transaction rolls back.
     * @return WalletTransaction The audit row that was written.
     */
    public function mutate(int $walletId, Closure $apply): WalletTransaction;

    /**
     * Paginated list of WalletTransaction rows for the given wallet,
     * newest first. Used by the user-facing transactions endpoint.
     *
     * @param  string|null  $type  Optional WalletTransactionType value to
     *                             filter on (CREDIT, DEBIT, LOCK, UNLOCK,
     *                             REFUND). Caller is responsible for
     *                             validating this against the enum's
     *                             values() — repository does not.
     */
    public function paginateTransactions(int $walletId, int $perPage, ?string $type = null): LengthAwarePaginator;
}
