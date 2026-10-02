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

### D. Webhook reconciliation diagnosis (legacy org, read-only + one command)
- Legacy org migrated to `stripe_accounts.id=1` (`is_legacy=1`); all rows stamped, webhooks correctly hit `/webhooks/stripe/1`. Scoping was NOT the bug.
- Real cause of `succeeded` + `unreconciled` items: `handleChargeSucceeded()` early-returns (silently, no log) when `charge.balance_transaction` is empty, and `payment_intent.succeeded`'s `reconcileQuietly()` runs while the BT is still pending → `null`. `charge.updated` is unhandled, so nothing retries. BTs existed in Stripe all along.
- Also: `stripe:reconcile-items` only picks up `status=succeeded`, so `processing`-stuck rows (missed webhooks) are never retried even when Stripe shows succeeded.
- Ran `php artisan stripe:reconcile-items --account=1 --days=7` → 2/2 reconciled. **Proposed (not implemented)**: log the empty-BT early return, retry reconcile on `charge.updated`, extend the command to refresh stale `processing` rows.

### E. Activity log modern UI + email body capture
- `ActivityLog` model gained presenters: `initials()`, `avatarUrl()`, `avatarColor()`, `eventTheme()`, `changesList()`, `remainingChangesCount()`, `previewValue()`.
- Index (`admin/activity-logs`): `?view=timeline|table` toggle (timeline default, invalid falls back), slim clickable feed grouped Today/Yesterday/date with vertically-centred avatars, inline first-change `old → new`, relative timestamps; **detail opens in an Alpine instant modal** (data embedded per page, `openLog(id)`), no navigation. Table view kept. `show` route kept as "Open full page" fallback + polished with avatar header.
- **Whole email bodies now logged**: `MessageSent` listener stores capped (150KB) `html_body`/`text_body` + `attachments` + `body_truncated` in `properties`. New `GET admin/activity-logs/{log}/body` (JSON, same `view-activity-log` gate) feeds a sandboxed (`sandbox=""`) iframe preview in the modal + direct preview on the show page. Old entries show "no body captured".
- `tailwind.config.js` safelist now pins event-theme classes (they live only in PHP strings — see gotcha 11); ran `npm run build` (current bundle `app-DBxRi_Qj.css`).
- Tests: `ActivityLogTest` (+timeline/modal cases), `ActivityCoverageTest` (+body capture/endpoint/detail cases).

### F. Split DDR vs onboarding emails + shared email layout
- New `App\Mail\DirectDebitConfigured` (subject `Direct Debit Configured Successfully for {Client}`), sent from `DdrController::store` (with `$client->company ?? $account->company` + account display name) and `OnboardingController::directDebitStore` (no company → app branding). Full `OnboardingController::store` still sends `NewClientCreated` unchanged.
- Masked account number (`•••• 1234`), mandate-status pill, primary contact, next-steps, View Client button.
- **One shared layout** `resources/views/emails/layout.blade.php` (logo header → colored band + badge → content → branded footer, responsive + preheader). All 7 emails converted: DDR + onboarding + quote (markdown→`view:`) and all 4 Stripe notifications (payout fragment gained its missing outer wrapper). Band colors: green success, red failed, amber dispute, indigo quote.
- Company logo per email: `asset('images/'.$company->logo)` (allinit.png fallback); notifications via new `HasStripeAccountContext::stripeAccountLogoUrl()`.
- Tests: `tests/Feature/Emails/DirectDebitEmailTest.php` (DDR/legacy-DD get new mail + branding; onboarding keeps old). Note `Mail::fake()` + `$mail->render()` works for content assertions.

## 3. Test suite status

- Full suite status when last touched: activity + email + payout-context suites green (18 + 6 passed). **2 pre-existing failures, both fail on clean HEAD too, do not chase unless asked**: `RegistrationTest > new users can register` (assigns a `customer` role missing from test DB); `PayoutAccountContextTest > payout notification resolves account from the local payout` (subject says `[Legacy]`, test expects `Legacy pool`).
- New suites since: `tests/Feature/Admin/ActivityLogTest.php`, `ActivityCoverageTest.php`, `tests/Feature/Emails/DirectDebitEmailTest.php` (+ Stripe `StripeAccountTest.php`, `MultiTenantStripeTest.php`, `WebhookSettlementTest.php`, `PayoutAccountContextTest.php`, `PayoutTransactionUiTest.php`, admin `UserManagementTest.php`, `RolePermissionTest.php` from before).
- Helper function names must be **unique across ALL Pest files** (all files load into one process). Existing: `makeAdmin`, `makeCompany`, `makeRoleAdmin`, `makeStripeCompany/Account`, `makeTenant*`, `makePayoutCompany/Account`, `makePayoutUi*`, `makeActivityViewer`, `makeCoverageViewer`, `makeDdrCompany/Account`, `bindFakeBecs`, `StubBecsService`, `StubWebhookController`, `stripeWebhookPost/Headers/Event`.
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
11. **Tailwind purges classes that only exist in PHP strings** (e.g. `ActivityLog::eventTheme()` palettes) — content scanner covers `resources/views/**` only. Either write classes literally in Blade or pin them in `tailwind.config.js` safelist, then `npm run build`. Several badge colors shipped unstyled because of this.
12. **`Mail::fake()` + `$mail->render()`** renders markdown AND html mailables in tests without sending — use it for subject/body assertions. `asset()` inside queued mail needs correct `APP_URL`.
13. Email preview uses `<iframe sandbox="" :srcdoc>` (modal, Alpine-bound) or `srcdoc="{{ ... }}"` (Blade-escaped, show page) — full HTML docs render fine inside srcdoc.
14. `MailMessage->view(...)` notification views are plain Blade (no `x-mail::` components allowed); `@extends('emails.layout', [...])` works and receives both parent + view data.

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
- **Reconciliation hardening (diagnosed §D, not built)**: log empty-BT early return in `handleChargeSucceeded`, retry `reconcilePaymentIntent` on `charge.updated`, extend `stripe:reconcile-items` to refresh stale `processing` rows.
- Past mail activity logs have **no bodies** (capture started with §E) — backfill is impossible; they show the fallback message by design.
