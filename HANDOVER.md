# HANDOVER — AIIT Onboarding (multi-tenant Stripe + User/Roles admin)

> Read this first in a fresh session. It captures everything built so far,
> why it was built that way, and every gotcha that cost time.

## 1. Project snapshot

- **App**: Laravel 12 (`laravel/framework ^12`), PHP 8.2+ (local runs 8.5), MySQL in prod, **SQLite `:memory:` in tests** (`phpunit.xml`).
- **Key packages**: `spatie/laravel-permission ^8`, `intervention/image ^4.3` (v4 API!), `stripe/stripe-php ^20`, Pest 3, Pint, Breeze, Tailwind v3 + Alpine.
- **Tests**: `php artisan test --compact`. Auth tests live under `tests/Feature/...`.
- **Style**: `vendor/bin/pint --dirty` before finishing. Pint reformats pre-existing files too — that's fine, style-only.

## 2. What was built (chronological)

### A. User Management CRUD upgrade
- `users` got `phone` + `avatar` columns; new `company_user` pivot (`User ↔ Company` many-to-many).
- Avatar pipeline: `App\Services\AvatarService` — `cover(400,400)` → WebP q82 → `public` disk; old file deleted on replace/delete.
- Reused Spatie roles; `PermissionSeeder` gained `manager` + `user` roles; admin got full user CRUD perms.
- Server-side auth via `StoreUserRequest`/`UpdateUserRequest` + controller `authorizeAction()`; only super-admins may assign `super-admin`.
- View `resources/views/admin/users/index.blade.php` follows the admin style (header slot, stat cards, DataTables, Alpine modal).

### B. Roles & Permissions CRUD
- New nullable columns: `roles.display_name`, `permissions.group` (+ index).
- `RoleController` / `PermissionController` + 4 Form Requests; super-admin rename/delete guards; role-with-users delete guard.
- Views `admin/roles/index`, `admin/permissions/index` with **module-grouped bulk check/uncheck**; permission bulk bar (move group / bulk delete) + quick search on top (sticky).
- Sidebar `layouts/navigation.blade.php` gained a **User Admin** section (Users, Roles, Permissions), each `@can`-gated.
- `PermissionSeeder` restructured as `group => [names]` (11 modules incl. "Stripe Accounts") and backfills groups + role display names on every run.

### C. Multi-tenant Stripe per Company (the big one)
- **Rule (user-mandated)**: routing by business context only (internal ids / uuids, never slugs); browser-supplied Stripe ids never pick credentials; per-account credentials/webhooks/customers/setup-intents.
- `stripe_accounts` table: `id`, `public_id` (uuid, DDR links), nullable `company_id`, `display_name`, `publishable_key`, `secret_key`/`webhook_secret` (`encrypted` casts, `$hidden`), `is_default`, `is_legacy`, `status`.
- Nullable FKs added: `clients.company_id`; `stripe_account_id` on `stripe_customers`, `stripe_payment_methods`, `stripe_charge_batches(+items)`, `direct_debit_payments` (+`company_id`), `stripe_payouts`, `stripe_balance_transactions`.
- `StripeAccountResolver` (`app/Services/`): `forCompany/forAccount/forPublicId/forStripeCustomer/legacyAccount/clientFor/publishableKeyFor` + shared `visibleAccounts(?User)` / `assertAccountVisible()` (super-admin sees all; members see company-less + own companies; users without companies see all).
- `StripeAccountSeeder`: idempotent; no-ops without `STRIPE_SECRET`; creates legacy account from env (`LEGACY_STRIPE_COMPANY_ID` or first company or none); backfills **only NULL** links (chunked, SQLite-safe).
- **Admin CRUD** `admin/stripe-accounts` (write-only secrets, default flag, DDR + webhook URLs with copy buttons), new `view/create/edit/delete-stripe-account` perms (super-admin full; admin view-only).
- **Clients**: `company_id` validated on update, edit-page select, index filter (incl. Unassigned) + badges.
- **DDR per company**: `DdrController`, `GET /ddr/{publicId}` (+ setup-intent + store POSTs); `ddr.blade.php` renders per-company branding/key or legacy globals. Legacy `/ddr` + `onboarding.*` untouched. DDR store stamps `company_id` + mirrors customer/PM with `stripe_account_id`.
- **Bulk per company**: account picker on bulk page; strict confirm per picked account; **All-mode splits selections into one batch per account pool** (review modal warns when spanning accounts); jobs/cancel charge through the item's account client; batch list + items list got filters + badges; batch-show names its account.
- **Webhooks per account**: `POST /webhooks/stripe/{stripeAccount}` verifies with that account's secret; customer/PM/mandate/DD/batch lookups scoped; new mirrors stamped; balance reads use account client. Legacy endpoint byte-identical behavior. Also fixed: null-safe invoice calls; `canceled`→`cancelled` normalization.
- **Payouts/BT sync**: `StripePayoutSyncService` takes optional account, stamps rows (guarded — legacy mode never wipes stamps); `stripe:sync-payouts --account=` added.
- **Notifications account/company-aware**: shared `HasStripeAccountContext` trait (`"Account — Company"` / `"Legacy pool"`); subjects suffixed `[label]`; account+company rows in all 4 email blades; payout notification takes optional account id (else local-row lookup); DD-failed mails hardened + name merchant company.
- **UI for payouts/transactions/batches**: account filters + badges everywhere; live-API transactions read through the picked account's keys (cache keys namespaced per account; API failures → error banner, never 500).

## 3. Test suite status

- Full suite: **~87 passed / ~395 assertions, 1 pre-existing failure**: `RegistrationTest > new users can register` — **fails on clean HEAD too** (registration assigns a `customer` role that doesn't exist in the test DB). Do not chase it unless asked.
- New suites: `tests/Feature/Stripe/StripeAccountTest.php`, `MultiTenantStripeTest.php`, `WebhookSettlementTest.php`, `PayoutAccountContextTest.php`, `PayoutTransactionUiTest.php`, `tests/Feature/Admin/UserManagementTest.php`, `RolePermissionTest.php`.
- Helper function names must be **unique across ALL Pest files** (all files load into one process). Existing: `makeAdmin`, `makeCompany`, `makeRoleAdmin`, `makeStripeCompany/Account`, `makeTenant*`, `makePayoutCompany/Account`, `makePayoutUi*`, `bindFakeBecs`, `StubBecsService`, `StubWebhookController`, `stripeWebhookPost/Headers/Event`.
- Webhook tests post **raw JSON bodies** via `test()->call(..., $server, $payload)` with HMAC computed as `hash_hmac('sha256', "$ts.$payload", $secret)` — `$this->post()` sends form data and breaks signatures.
- `StubWebhookController` (in WebhookSettlementTest) subclasses the controller overriding protected `becs()` so per-account settlement tests avoid network. `app()->instance()` works for constructor injection but **loses to `app(X, $params)`** (verified empirically) — production `becs()` uses `new`, keep it that way.
- Sync queue in tests: jobs run inline; dummy Stripe keys fail fast with auth errors (safe). A past 300s timeout was a transient network stall, not code.
- `Schema::disableForeignKeyConstraints()` **does not work inside SQLite transactions** (RefreshDatabase) — build real FK chains in fixtures instead.
- Fixture NOT NULL/FK traps: `stripe_customers.stripe_data` NOT NULL; `stripe_payment_methods.stripe_customer_id` is a **numeric** FK (not the `cus_` string); `stripe_payment_method_id` is DB-UNIQUE; `direct_debit_payments` needs real Xero chain (user→connection→tenant→invoice).

## 4. Gotchas that bit before

1. **Intervention Image v4** (`4.x`): `decode()` + `new WebpEncoder(quality:)`, NOT v3's `read()`/`toWebp()`.
2. **Blade `@json(...)` with complex inline expressions can silently miscompile** (produced `Unclosed '['` ParseError). Always build `@php $editPayload = [...]` first, then `@json($editPayload)`.
3. **`actingAs()` persists across requests within one test** — put guest assertions BEFORE any `actingAs`, or they run authenticated.
4. **MySQL-only migration** `2026_09_23_080000_...` uses raw `MODIFY` — guarded to run only on MySQL so SQLite tests pass. Keep raw SQL out of migrations.
5. **Pint reformats untouched files** — normal; don't revert.
6. `StripeClient` with null secret + real calls in tests: existing bulk tests rely on fast auth failure; never assert on live data.
7. Seeder backfills must be `whereNull`-only + chunked (SQLite has no UPDATE..JOIN).
8. `visibleAccounts()` lives on the **resolver** — reuse it; don't duplicate per controller.
9. `clients.company_name` is still free text; `company_id` is the real link (nullable, "Unassigned" queue in UI).
10. Payout/BT tables have **no company_id** (account only — company via relation). Xero mapping is **out of scope** (user decision).

## 5. Deploy / runbook

```bash
composer install
php artisan migrate --force
php artisan db:seed --class=PermissionSeeder   # role/permission + stripe-account perms, groups, display names
php artisan db:seed --class=StripeAccountSeeder # legacy account from STRIPE_* env + backfill NULLs only
php artisan storage:link
php artisan test --compact
```
- Optional: `LEGACY_STRIPE_COMPANY_ID=<id>` to pin the legacy account's owner.
- Post-deploy per company: register its webhook URL (shown on Stripe Accounts page: `/webhooks/stripe/{id}`) in that company's Stripe dashboard; share its DDR link (`/ddr/{public_id}`).

## 6. Open / suggested next steps

- Batches **list** has filter+badges; all detail pages covered. Nothing known pending.
- Possible follow-ups if asked: `company_id` on payouts/BTs (currently account-only); Xero tenant↔company mapping (explicitly out of scope); cut over legacy `/ddr` + global webhook (currently parallel on purpose); `stripe:sync-payouts` per-account scheduling.
- Legacy `/ddr` route closure renders `ddr` view with no `$stripeAccount` — the view handles it via `$stripeAccount ?? null`. Don't break that.
