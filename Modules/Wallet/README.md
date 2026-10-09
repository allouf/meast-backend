# Wallet Module

Per-user platform-services wallet for **Flow B** (user → platform payments).
**Not used for marketplace** (Flow A — buyer ↔ seller — runs on a separate fiat
escrow ledger).

## What this module does today

After Feature 5 (`feature/wallet-refunds`) it establishes:

- Four tables: `wallets`, `wallet_transactions`, `wallet_topup_intents`, `wallet_refunds`.
- The three-balance model on every wallet: `available`, `pending`, `locked`.
- Identity / read methods: `getOrCreate`, `getBalances`, `getTransactionsPaginated`.
- Five mutation methods that move funds between balance buckets and write
  one audit row each: `credit`, `debit`, `lock`, `unlock`, `refund`.
- Domain exceptions for the validation cases: `InsufficientFundsException`,
  `InsufficientLockedFundsException`, `InvalidAmountException`.
- **User-facing HTTP API**: `GET /api/v1/wallet`, `GET /api/v1/wallet/transactions`
  (auth:api JWT, paginated transactions list).
- **Fiat top-up via Stripe Checkout**: `POST /api/v1/wallet/topup` initiates
  a hosted-Checkout session; `POST /api/v1/wallet/webhooks/stripe` confirms
  payment and credits the wallet. Idempotent — duplicate webhooks won't
  double-credit.
- **Refund handling**: same webhook endpoint also processes `charge.refunded`
  events. Each refund creates a `WalletRefund` row and a `REFUND`
  transaction; the wallet's `available_balance` is decremented and is
  ALLOWED to go negative (representing user debt when refunded amount
  exceeds remaining balance).

It does **NOT yet do**:

- Crypto deposit address generation, blockchain watchers (Feature 5).
- Spend integration with subscription/ad billing (Feature 6).
- Admin / Seller HTTP endpoints (will arrive when those personas need wallet
  visibility — separate small PRs).
- Other gateways beyond Stripe (HyperPay / Moyasar / Tap / etc. follow the
  same pattern as `StripeTopupService` — separate PRs per gateway).

Each future feature is a separate branch / PR.

## The three-balance model

| Balance | What it represents |
| --- | --- |
| `available` | Funds the user can spend on platform fees right now. |
| `pending` | Incoming funds awaiting blockchain confirmations or settlement. Do not spend. |
| `locked` | Reserved against active subscriptions or ad commitments. Released back to `available` when the commitment ends. |

All three are stored in the same denomination unit, recorded in the `currency`
column. For Feature 1 the default is `USD` (hard-coded in `WalletService`); once
the unit-of-account decision (Checklist Row #1 in the management brief) is made,
this becomes a config value.

## The audit trail

Every change to any balance writes one row to `wallet_transactions`. Each row
records:

- The wallet affected and the transaction `type` (CREDIT / DEBIT / LOCK / UNLOCK).
- The amount, plus the balance values **before and after** the change.
  Storing both makes dispute investigation O(1) per row instead of requiring
  a sum from the beginning of time.
- An optional polymorphic reference to the source entity (e.g. `Order #1234`,
  `Payment #99`). Manual adjustments leave both reference fields null.
- Free-form `metadata` JSON for gateway transaction ids, blockchain hashes,
  idempotency keys, etc.

The table is append-only in normal operation. Never `UPDATE` or `DELETE`
existing rows.

## How to use the module from other code

Always inject the **service**, not the repository directly. Repositories are an
implementation detail.

### Reading

```php
use Modules\Wallet\Services\WalletService;

class SomeController
{
    public function __construct(private readonly WalletService $wallets) {}

    public function show(int $userId)
    {
        $balances = $this->wallets->getBalances($userId);
        // ['available' => '0.00000000', 'pending' => '...', 'locked' => '...', 'currency' => 'USD']
    }
}
```

### Mutating balances

All four mutation methods have the same signature shape:

```php
$tx = $this->wallets->credit($userId, '100.00', $reference, $metadata);
$tx = $this->wallets->debit ($userId,   '30.00', $reference, $metadata);
$tx = $this->wallets->lock  ($userId,   '50.00', $reference, $metadata);
$tx = $this->wallets->unlock($userId,   '20.00', $reference, $metadata);
```

| Argument | Type | Notes |
|---|---|---|
| `$userId` | `int` | The wallet's owner. The wallet is created on first call if it doesn't exist. |
| `$amount` | `string` | Positive decimal string with up to 8 fractional digits. **Do not pass floats.** Examples: `'100'`, `'100.50'`, `'0.00000001'`. |
| `$reference` | `?array{type:string,id:int}` | Optional polymorphic link to the source entity. E.g. `['type' => 'App\\Models\\Order', 'id' => 1234]`. Recommended whenever there is one. |
| `$metadata` | `?array<string,mixed>` | Optional free-form context (gateway tx id, blockchain hash, idempotency key, etc.) stored as JSON. |

Each method returns the persisted `WalletTransaction` audit row.

### Semantics

| Method | What it does | Reads / writes | Notes |
|---|---|---|---|
| `credit` | Adds to `available_balance`. | available ↑ | |
| `debit` | Subtracts from `available_balance`. | available ↓ | Refuses if insufficient |
| `lock` | Moves funds from `available` to `locked`. | available ↓, locked ↑ | Refuses if insufficient |
| `unlock` | Moves funds from `locked` back to `available`. | locked ↓, available ↑ | Refuses if insufficient locked |
| `refund` | Subtracts from `available_balance` (REFUND tx type). | available ↓ | **Allows negative balance** — used by gateway refund webhooks |

The `wallet_transactions.balance_before` and `balance_after` columns refer to
the bucket that **changed semantically**:

- For `credit` / `debit` → the `available` bucket.
- For `lock` / `unlock` → the `locked` bucket. (The `available` change is the
  mirror counterpart.)

### Error handling

```php
use Modules\Wallet\Exceptions\InsufficientFundsException;
use Modules\Wallet\Exceptions\InsufficientLockedFundsException;
use Modules\Wallet\Exceptions\InvalidAmountException;
use Modules\Wallet\Exceptions\WalletException;

try {
    $this->wallets->debit($userId, $amount, $reference);
} catch (InsufficientFundsException $e) {
    // available < amount; show user a friendly "top up your wallet" message
} catch (InvalidAmountException $e) {
    // malformed input; this is a bug in the caller, not user input
} catch (WalletException $e) {
    // catch-all for any wallet-domain failure
}
```

Mutations are transactional: if anything throws, the wallet balance and the
audit row both roll back atomically. There is no state where the balance
changed but no audit row was written, or vice versa.

## HTTP API (user-facing)

All endpoints require an authenticated user (`auth:api`, JWT). The wallet
returned is always the authenticated user's own wallet — clients can never
query another user's wallet.

### `GET /api/v1/wallet`

Returns the current user's wallet (creates it on first call).

```json
{
  "status": true,
  "message": "Data retrieved successfully.",
  "data": {
    "id": 1,
    "available_balance": "100.00000000",
    "pending_balance":   "0.00000000",
    "locked_balance":    "50.00000000",
    "currency": "USD",
    "created_at": "2026-04-26T20:15:38+00:00",
    "updated_at": "2026-04-27T08:42:11+00:00"
  }
}
```

### `GET /api/v1/wallet/transactions`

Paginated list of the current user's wallet transactions, newest first.

| Query param | Default | Range | Description |
|---|---|---|---|
| `per_page` | `15` | `1..100` (clamped) | Items per page |
| `page`     | `1`  | any positive integer | Page number |

```json
{
  "status": true,
  "message": "Data retrieved successfully.",
  "data": {
    "data": [
      {
        "id": 42,
        "type": "DEBIT",
        "amount":         "30.00000000",
        "balance_before": "100.00000000",
        "balance_after":  "70.00000000",
        "reference":      { "type": "App\\Models\\Order", "id": 1234 },
        "metadata":       { "gateway_tx": "pi_xxx" },
        "created_at":     "2026-04-27T08:42:11+00:00"
      }
    ],
    "meta": {
      "current_page": 1,
      "per_page":     15,
      "total":        42,
      "last_page":    3
    }
  }
}
```

Both endpoints return the standard envelope (`status`, `message`, `data`)
provided by `App\Traits\BaseResponse`.

### `POST /api/v1/wallet/topup`

Initiates a fiat top-up via Stripe Checkout.

**Request body**
```json
{ "amount": "50.00" }
```

`amount` is in major units of the configured currency (USD by default),
between `services.stripe.topup_min` and `services.stripe.topup_max` (1–1000
USD by default), at most 2 decimal places.

**Successful response (200)**
```json
{
  "status": true,
  "message": "Top-up initiated. Redirect to checkout_url to complete the payment.",
  "data": {
    "intent": {
      "id": 1,
      "amount":            "50.00000000",
      "currency":          "usd",
      "status":            "PENDING",
      "stripe_session_id": "cs_test_a1b2c3...",
      "stripe_payment_intent_id": null,
      "failure_reason":    null,
      "completed_at":      null,
      "created_at":        "2026-04-27T15:42:11+00:00"
    },
    "checkout_url": "https://checkout.stripe.com/c/pay/cs_test_a1b2c3..."
  }
}
```

**Frontend flow:** redirect (or open in a webview) to `data.checkout_url`.
Stripe handles the payment UI. On success/cancel, Stripe redirects back to
the URLs configured in `services.stripe.success_url` / `cancel_url`.

The wallet is **not** credited synchronously — the credit happens when
Stripe sends the webhook (next endpoint). Frontend should re-fetch
`GET /api/v1/wallet` after returning from Stripe to see the updated balance.

### `POST /api/v1/wallet/webhooks/stripe`

**Not for clients to call.** Stripe sends webhook events here when
payments complete / fail / expire.

- No `auth:api` middleware — Stripe doesn't have credentials.
- Authentication is via the `Stripe-Signature` header, verified inside the
  controller using the webhook signing secret.
- On `checkout.session.completed` with `payment_status=paid`:
  the matching `WalletTopupIntent` row is marked `COMPLETED` and the wallet
  is credited via `WalletService::credit()` — written as an audit row with
  `reference_type=WalletTopupIntent` and gateway metadata.
- Idempotent: if Stripe re-delivers the same event, we recognize the
  session id and skip.

### Configuration (`config/services.php`)

```php
'stripe' => [
    'key'             => env('Stripe_Publishable_key'),
    'secret'          => env('Stripe_Secret_key'),
    'webhook_secret'  => env('STRIPE_WEBHOOK_SECRET'),
    'topup_currency'  => env('WALLET_TOPUP_CURRENCY', 'usd'),
    'topup_min'       => (float) env('WALLET_TOPUP_MIN', 1),
    'topup_max'       => (float) env('WALLET_TOPUP_MAX', 1000),
    'success_url'     => env('STRIPE_SUCCESS_URL', env('APP_URL') . '/wallet?topup=success'),
    'cancel_url'      => env('STRIPE_CANCEL_URL',  env('APP_URL') . '/wallet?topup=cancel'),
],
```

To run locally with Stripe CLI:

```bash
stripe listen --forward-to localhost:8000/api/v1/wallet/webhooks/stripe
```

The CLI prints a `whsec_...` value — set it as `STRIPE_WEBHOOK_SECRET` in `.env`.

## File map

```
Modules/Wallet/
├── app/
│   ├── Enums/
│   │   └── WalletTransactionType.php          # CREDIT / DEBIT / LOCK / UNLOCK
│   ├── Exceptions/
│   │   ├── WalletException.php                # base class
│   │   ├── InsufficientFundsException.php     # available < requested
│   │   ├── InsufficientLockedFundsException.php
│   │   └── InvalidAmountException.php         # malformed amount input
│   ├── Http/
│   │   ├── Controllers/User/
│   │   │   └── WalletController.php           # show, transactions
│   │   └── Resources/User/
│   │       ├── WalletResource.php
│   │       └── WalletTransactionResource.php
│   ├── Models/
│   │   ├── Wallet.php                         # belongsTo User, hasMany WalletTransaction
│   │   ├── WalletTransaction.php              # MorphTo reference, metadata JSON
│   │   ├── WalletTopupIntent.php              # one row per Stripe Checkout session
│   │   └── WalletRefund.php                   # one row per Stripe refund (idempotent on stripe_refund_id)
│   ├── Providers/
│   │   ├── WalletServiceProvider.php          # registers the repository binding
│   │   └── RouteServiceProvider.php           # mapUserRoutes -> /api/v1
│   ├── Repositories/
│   │   ├── Interfaces/
│   │   │   └── WalletRepositoryInterface.php  # findByUserId, getOrCreateForUser, mutate, paginateTransactions
│   │   └── Eloquent/
│   │       └── WalletRepository.php           # uses lockForUpdate inside DB::transaction
│   ├── Services/
│   │   └── WalletService.php                  # getOrCreate, getBalances, getTransactionsPaginated, credit, debit, lock, unlock
│   └── Support/
│       └── Amount.php                         # decimal-string validation + bcmath arithmetic
├── database/
│   └── migrations/
│       ├── 2026_04_26_000001_create_wallets_table.php
│       └── 2026_04_26_000002_create_wallet_transactions_table.php
├── lang/
│   ├── en/messages.php
│   └── ar/messages.php
└── routes/
    └── user.php                               # GET /api/v1/wallet[/transactions]
```

## Open questions tied to management decisions

These are noted here so future contributors don't accidentally hard-code
something the management decision will rewrite. The full list of open
decisions lives in the management brief (`payment-strategy-brief.html`) — only
those that affect this module are shown here.

| Brief decision | What it changes in this module |
| --- | --- |
| **Row #1 — MEEC pegging model** (peg / volatile) | The default `currency` for new wallets, and whether a quote-lock layer is needed at the spend boundary. Until decided, `USD` is the safe placeholder. |
| **Row #5 — MEEC discount size** | The discount engine that consumes wallet balances at platform-fee checkout. Not in this module, but the wallet must be able to record the discount amount in `metadata` for accounting. |
| **Row #6 — Eligible platform services** | Determines which spend flows hit this wallet. No effect on the wallet's own structure. |

## What's next

| Feature | Branch | Adds |
| --- | --- | --- |
| ~~2~~ | ~~`feature/wallet-mutations`~~ | **Done.** Credit / debit / lock / unlock methods + audit writes. |
| ~~3~~ | ~~`feature/wallet-user-endpoints`~~ | **Done.** User-facing HTTP routes for "my wallet" and "my transactions". |
| ~~4~~ | ~~`feature/wallet-fiat-topup`~~ | **Done.** Stripe Checkout fiat top-up + webhook → wallet credit. |
| ~~5~~ | ~~`feature/wallet-refunds`~~ | **Done.** Stripe `charge.refunded` webhook → WalletRefund + REFUND transaction (allows negative balance). |
| 6 | `feature/wallet-crypto-deposits` | Per-user deposit address generation, blockchain watcher, pending-to-available promotion on confirmation. **Blocked on Row #2 (L2 choice)**. |
| 7 | `feature/wallet-platform-fee-spending` | Integration with subscription billing, ad packages, etc. — the actual debits against this wallet. Should refuse if `available_balance < 0` (user has unpaid debt). |
| Future | `feature/wallet-topup-{gateway}` | Additional gateways (HyperPay, Moyasar, Tap, PayTabs, etc.) follow the same pattern as `StripeTopupService` — one PR per gateway. |
