<?php

namespace Modules\Wallet\Services\Payment;

use Modules\Wallet\Contracts\PaymentRailInterface;
use Modules\Wallet\Enums\PaymentRail;
use Modules\Wallet\Exceptions\NoSupportedRailException;

/**
 * Picks the right PaymentRailInterface implementation for an InvoiceContext.
 *
 * Resolution priority:
 *   1. If the caller passed `preferredRail` AND that rail supports() the
 *      context, use it. (Frontend tells us "user picked card" or "user picked
 *      crypto" — we honour their choice as long as the rail can deliver.)
 *   2. Otherwise iterate registered rails in registration order; the FIRST
 *      one whose supports() returns true wins.
 *   3. If none support, throw NoSupportedRailException.
 *
 * Rails are registered via register() at boot time — typically in
 * WalletServiceProvider. Order of registration matters for the fallback
 * pass: more-specific rails should be registered before generic ones.
 */
class RailResolver
{
    /** @var array<string, PaymentRailInterface> Keyed by rail name. */
    private array $rails = [];

    public function register(PaymentRailInterface $rail): void
    {
        $this->rails[$rail->name()->value] = $rail;
    }

    /**
     * Resolve the best rail for the given context.
     *
     * @throws NoSupportedRailException If no registered rail can handle it.
     */
    public function resolve(InvoiceContext $ctx): PaymentRailInterface
    {
        // 1. Honour preferredRail if it's registered AND can handle the context.
        if ($ctx->preferredRail !== null && isset($this->rails[$ctx->preferredRail])) {
            $candidate = $this->rails[$ctx->preferredRail];
            if ($candidate->supports($ctx)) {
                return $candidate;
            }
        }

        // 2. Fallback: first-supports-wins in registration order.
        foreach ($this->rails as $rail) {
            if ($rail->supports($ctx)) {
                return $rail;
            }
        }

        // 3. Nothing matched — caller will get a 422 with this message.
        throw new NoSupportedRailException(sprintf(
            'No payment rail can handle this request (kind=%s, marketplace=%s, preferredRail=%s).',
            $ctx->kind,
            $ctx->isMarketplace() ? 'yes' : 'no',
            $ctx->preferredRail ?? 'none',
        ));
    }

    /** Useful for diagnostics / route:list-style introspection. */
    public function registered(): array
    {
        return array_keys($this->rails);
    }

    /**
     * Look up a registered rail by its name (e.g. 'stripe_connect').
     *
     * Used by callers that already know which rail handled a past
     * invoice and need to issue a follow-up action against it
     * (refund, status fetch). Different from resolve() — that picks a
     * rail for a NEW context; this is for an EXISTING invoice's rail.
     *
     * @throws \InvalidArgumentException If no rail with this name is registered.
     */
    public function byName(string $name): PaymentRailInterface
    {
        if (! isset($this->rails[$name])) {
            throw new \InvalidArgumentException(sprintf(
                "Unknown payment rail '%s'. Registered: %s",
                $name,
                implode(', ', array_keys($this->rails)),
            ));
        }

        return $this->rails[$name];
    }
}
