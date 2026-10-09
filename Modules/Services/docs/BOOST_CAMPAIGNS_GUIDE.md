# Boost campaigns — logic, flows, and APIs

This document describes **how boosts work end-to-end** in the Services module: data model, business rules, **reporting amounts** (no in-app wallet), moderation, admin controls, and public search ranking.

A ready-to-import Postman collection lives at **`postman/Boost_Campaigns.postman_collection.json`** (variables: `base_url`, `base_url_admin`, tokens, IDs).

---

## 1. Concepts

| Concept | Meaning |
|---------|---------|
| **Boost plan** | Admin-defined product: duration, base price (+ optional **country variants**), JSON **targeting/placement** (rank multiplier, sponsored badge). Can be disabled via `toggle-status`. |
| **Boost campaign** | A paid boost slot on **one service listing**, for a fixed **time window** (`start_at` → `end_at`), **ACTIVE / PAUSED / TERMINATED / …**. **`amount` + `currency`** mirror the resolved plan price for **reporting and notional (pro‑rata) refund math**; **collection of real money is out of band** (not handled by this module). |
| **Targeting scope** | **Server-built** snapshot on purchase: placement + audience (category + provider geo hints). Providers do **not** send targeting in the POST body. |
| **Notional refund** | When a campaign is **terminated early** (moderation or admin), the API computes **pro‑rata remaining value** from the **recorded** `amount` and remaining window. That figure is logged and may be returned to the client as **`notional_refund`** — it does **not** trigger automatic wallet credits. |

---

## 2. High-level lifecycle

```mermaid
sequenceDiagram
  participant Admin as Admin (plans/policy)
  participant Prov as Provider
  participant API as Services API
  participant Pub as Buyer (browse API)

  Admin->>API: Manage boost plans + category boost_prohibited
  Prov->>API: POST boost-campaigns (offer + plan)
  API->>API: Validate listing + category + MVP duration + price resolution
  API->>API: ACTIVE campaign (or extend end_at if stacking); store amount/currency for reporting

  Note over API: Listing moderated (status/is_active change)
  API->>API: Observer terminates boosts + logs notional_refund_amount (no payout)

  Note over Admin: Admin terminate
  Admin->>API: POST terminate
  API->>API: TERMINATED + audit meta (notional_refund_amount); response notional_refund (informational)
```

---

## 3. Provider flow (purchase & “my boosts”)

### Preconditions

- Authenticated **`auth:api`** + **`validate.device`** + **`service.profile.provider`**.
- **`ServiceOffer`**: **`is_active`**, **`status = active`** (published). Draft / pending / paused / rejected **cannot** be boosted.
- **Category**: `service_categories.boost_prohibited = false`.
- **Plan**: exists, **`is_active`**, **`duration_days` ∈ {3, 7, 30}** (MVP guard).

### Purchase (`POST /api/v1/boost-campaigns`)

**Body (only):**

```json
{ "service_offer_id": 12, "boost_plan_id": 3 }
```

**Inside one DB transaction (simplified):**

1. **Lock** the `service_offers` row (same provider) to serialize concurrent purchases on the same listing.
2. Re-check eligibility + category + plan + duration + price resolution (base vs **active variant** for `provider.country_code`).
3. **Lock** existing **ACTIVE** campaign in the time window for that offer (if any) — **stacking rule**.
4. Either:
   - **Create** new row: `status = ACTIVE`, `start_at = now`, `end_at = now + duration_days`, **`amount` / `currency`** from the plan (reporting), **`targeting_scope`** built server-side; or
   - **Extend** existing: `end_at += duration_days`, accumulate **`amount`**, refresh **`targeting_scope`** / **`boost_plan_id`**.

**Failure modes (422 / 404):** not found offer, inactive plan, prohibited category, wrong MVP duration.

### List campaigns (`GET /api/v1/boost-campaigns`)

Query: `page`, `per_page`, optional **`status`** (enum string).

---

## 4. `targeting_scope` stored on campaigns (contract)

Structured JSON like:

```json
{
  "version": 1,
  "placement": {
    "search_rank_multiplier": 1.25,
    "show_sponsored_badge": true
  },
  "audience": {
    "same_category_as_listing": true,
    "service_category_id": 5,
    "geo": { "country_code": "SA", "city_or_region": "Riyadh" }
  }
}
```

- **Placement** derives from **`boost_plans.targeting`** (`search_rank_multiplier`, `show_sponsored_badge`); sane defaults apply if omitted.
- **Audience** derives from listing category + provider `country_code` + `region` (city surrogate).

---

## 5. Termination & moderation (automatic)

**Trigger:** `ServiceOfferObserver`, when **`is_active`** or **`status`** changes and the offer becomes **ineligible for purchase** (`!isEligibleForPaidBoostPurchase()`).

**Effect:** Every **ACTIVE campaign in-window** for that offer is **`TERMINATED`**, **`terminated_at` now**. A **`boost_campaign_logs`** row is written with **`action = TERMINATED_MODERATION`**, optional trace **`reason`**, and **`meta.notional_refund_amount`** (pro‑rata of recorded amount). **No wallet or payout** is performed by this flow.

**Admin terminate** uses the same pro‑rata formula for **`meta.notional_refund_amount`** and returns **`notional_refund`** in the JSON body for operators; again, **informational only**.

---

## 6. Admin flow (inventory, campaigns, audit)

**Base:** `/api/v1/admin/...`, **`auth:admin`**, **`role:admin`**.

### Boost plans (`/boost-plans`)

- List/show/create/update/delete, **`toggle-status/{id}`** to disable purchases.
- **Variants** encode **country-specific** prices/currencies (`boost_plan_variants`).

### Boost campaigns (`/boost-campaigns`)

| Method | Path | Purpose |
|--------|------|---------|
| `GET` | `/boost-campaigns` | Filters: `status`, `active_window_only`, `search`, `provider_id`, `service_offer_id`, pagination |
| `GET` | `/boost-campaigns/show/{id}` | Campaign + nested offer/provider/plan + **recent logs** |
| `POST` | `/boost-campaigns/{id}/pause` | Optional JSON `{ "reason": "..." }` → **`boost_campaign_logs`**, **`PAUSED`** |
| `POST` | `/boost-campaigns/{id}/terminate` | Optional `reason`; **`TERMINATED`** + audit **`meta.notional_refund_amount`**; response includes **`notional_refund`** (no automatic credit) |

**Audit:** `boost_campaign_logs` stores **`admin_id`** (references **`admins`** after consolidation migration; null for moderation-driven terminations), **`action`**, **`reason`**, **`meta`** JSON.

### Policy on categories

`POST /service-categories/update/{id}` may include **`boost_prohibited`** (boolean). When `true`, **new** boosts are rejected for listings in that category.

---

## 7. Public browse & ranking (`GET /api/v1/services`)

**Goal:** uplift boosted, **relevance-first**, with **guardrails** and a **frozen snapshot** for consistent ordering during pagination.

Pipeline (conceptual):

1. **Filters** from query (category, price, search, **`viewer_country_code`**, **`sort`**, …) — boosted rows must still satisfy **the same predicates** as organic rows (`ServiceOfferFilter` without default sort).
2. **Ranking pool**: if filtered set exceeds **1500** rows, rerank **newest slice** (`updated_at`, `id`); **`meta.ranking_pool_limited = true`** but **`total`** reflects full filter count).
3. For each candidate at **`ranking_snapshot_at`**: compute **base relevance** from text/category/geo-viewer hint/rating/freshness; multiply by **bounded** plan multiplier (cap **1.5**) if an **ACTIVE** boost covers the snapshot instant.
4. **Guardrails** (post-order rewrite):
   - **Sponsor density:** at most **2 ranking-boost slots per sliding window of 20** (extra boosted listings may stay **sponsored** but rank as organic).
   - **Provider spread:** if the same provider would overload a trailing window (**>3 in 12**), that row’s **ranking boost** is withheld (still listed).
5. Pagination slices the final ordered list. Response **`meta`** includes **`ranking_snapshot_at`**, **`ranking_pool_limited`**.

Listing fields (typical):

- **`is_sponsored`** — active boost window at snapshot.
- **`boost_score`** — effective **multiplier** used for positioning (may be **1.0** if guardrails withheld ranking boost).
- **`ranking_boost_applied`** — whether multiplier uplift counted for ordering on this response.
- **`ranking`** — optional transparency: `base_relevance_score`, `boost_multiplier_effective`.

**Detail (`GET /api/v1/services/show/{id}`)** exposes **`is_sponsored`** / **`boost_score`** using live relations (no ranking snapshot semantics).

---

## 8. Endpoint map (quick)

| Actor | Endpoint |
|-------|----------|
| Public | `GET /api/v1/services`, `GET /api/v1/services/show/{id}` |
| Provider | `GET|POST /api/v1/boost-campaigns` |
| Admin | `…/admin/boost-plans/*`, `…/admin/boost-campaigns/*`, category update for `boost_prohibited` |

---

## 9. Related code (navigation)

| Area | Location |
|------|----------|
| Purchase + stacking + moderation terminate | `app/Services/BoostCampaignService.php` |
| Public ranking | `app/Services/ServiceOfferPublicSearchService.php` |
| Filters + optional `viewer_country_code` | `app/Filters/ServiceOfferFilter.php` |
| Offer observer | `app/Observers/ServiceOfferObserver.php` |
| Provider routes | `routes/provider.php` |
| Admin routes | `routes/admin.php` |
| User browse | `routes/user.php` (`ServiceListingController`) |

---

## 10. Operational checklist

1. Run migrations (boost tables, **`boost_prohibited`**, **`boost_campaign_logs` → admins** FK). If an older migration added **`wallet_transaction_id`** to **`boost_campaigns`**, run the follow-up migration that **drops** that column — boosts no longer reference the Wallet module.
2. Seed **plans** with valid **duration_days** and optional **variants**.
3. Import **`postman/Boost_Campaigns.postman_collection.json`** and fill `access_token`, `access_token_admin`, IDs.
