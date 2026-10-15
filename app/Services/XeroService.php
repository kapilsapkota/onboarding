<?php

namespace App\Services;

use App\Exceptions\XeroAuthException;
use App\Models\Client;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\XeroConnection;
use App\Models\XeroTenant;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class XeroService
{
    private const BASE_URL = 'https://api.xero.com/api.xro/2.0';

    private const IDENTITY_URL = 'https://api.xero.com/connections';

    private const TOKEN_URL = 'https://identity.xero.com/connect/token';

    private const AUTHORIZE_URL = 'https://login.xero.com/identity/connect/authorize';

    public function __construct(
        private string $clientId = '',
        private string $clientSecret = '',
        private string $redirectUri = '',
    ) {
        $this->clientId = config('services.xero.client_id');
        $this->clientSecret = config('services.xero.client_secret');
        $this->redirectUri = config('services.xero.redirect_uri');
    }

    // ─────────────────────────────────────────────
    // OAuth
    // ─────────────────────────────────────────────

    public function getAuthorizationUrl(): string
    {
        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => 'openid profile email accounting.transactions accounting.contacts offline_access',
            'state' => csrf_token(),
        ]);

        return self::AUTHORIZE_URL.'?'.$params;
    }

    public function exchangeCode(string $code): array
    {
        $response = Http::asForm()
            ->withBasicAuth($this->clientId, $this->clientSecret)
            ->post(self::TOKEN_URL, [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->redirectUri,
            ]);

        $response->throw();

        return $response->json();
    }

    /**
     * Ensure the connection holds a valid, non-expired access token.
     *
     * Flow:
     *   1. Token still has 5+ minutes left → return as-is (no network call).
     *   2. Token is expiring / expired → acquire a distributed lock and refresh.
     *   3. If another process already refreshed while we waited for the lock,
     *      re-fetch the row and skip the HTTP call (double-checked locking).
     *   4. If Xero returns 400/401 → mark needs_reauth = true, throw XeroAuthException.
     *   5. Lock could not be acquired within 15 s → re-fetch (the other process
     *      most likely succeeded) and return whatever we get.
     *
     * @throws XeroAuthException When the refresh token is revoked/expired.
     * @throws RequestException On any other Xero HTTP error.
     */
    public function refreshToken(XeroConnection $connection): XeroConnection
    {
        // Fast path — token is still fresh.
        if ($connection->token_expires_at?->gt(now()->addMinutes(5))) {
            return $connection;
        }

        $lockKey = 'xero-refresh-'.$connection->id;

        // Try to acquire the lock for up to 15 seconds.
        $refreshed = Cache::lock($lockKey, 30)->block(15, function () use ($connection) {
            // Re-fetch inside the lock so we get the latest token (another process
            // may have just refreshed it while we were waiting).
            $connection = $connection->fresh();

            // Double-check: maybe another process already refreshed it.
            if ($connection->token_expires_at?->gt(now()->addMinutes(5))) {
                return $connection;
            }

            // At this point we hold the lock and the token is genuinely stale.
            Log::info('XeroService: refreshing access token', ['connection_id' => $connection->id]);

            try {
                $response = Http::asForm()
                    ->withBasicAuth($this->clientId, $this->clientSecret)
                    ->post(self::TOKEN_URL, [
                        'grant_type' => 'refresh_token',
                        'refresh_token' => $connection->refresh_token,
                    ]);

                $response->throw();

                $data = $response->json();

                $connection->update([
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'],
                    'token_expires_at' => now()->addSeconds($data['expires_in']),
                    // Clear any previous reauth flag on successful refresh.
                    'needs_reauth' => false,
                    'reauth_reason' => null,
                ]);

                return $connection->fresh();

            } catch (RequestException $e) {
                $status = $e->response->status();

                // 400 / 401 means the refresh token itself is dead (revoked,
                // rotated, or the Xero app was disconnected). Nothing we can do
                // automatically — an admin must re-authorize via OAuth.
                if (in_array($status, [400, 401], true)) {
                    $reason = 'Refresh token revoked or expired (HTTP '.$status.'). Re-authorization required.';

                    Log::error('XeroService: refresh token is invalid, marking needs_reauth', [
                        'connection_id' => $connection->id,
                        'status' => $status,
                        'body' => $e->response->body(),
                    ]);

                    $connection->update([
                        'is_active' => false,
                        'needs_reauth' => true,
                        'reauth_reason' => $reason,
                    ]);

                    throw new XeroAuthException($reason);
                }

                // Any other HTTP error (5xx, rate-limit, etc.) — surface it but
                // do NOT mark needs_reauth; a transient outage shouldn't force
                // the admin to click through OAuth again.
                throw $e;
            }
        });

        // Lock::block() returns null only if the lock was NOT acquired within
        // the timeout. In that case another process was likely refreshing; grab
        // the freshest row we can and proceed — it's probably valid now.
        if ($refreshed === null) {
            Log::warning('XeroService: could not acquire refresh lock, using fresh row', [
                'connection_id' => $connection->id,
            ]);

            $connection = $connection->fresh();

            // If the fresh row still needs reauth, raise now rather than
            // attempting API calls with a dead token.
            if ($connection->needs_reauth) {
                throw new XeroAuthException($connection->reauth_reason ?? 'Re-authorization required.');
            }

            return $connection;
        }

        return $refreshed;
    }

    // ─────────────────────────────────────────────
    // Connection helpers
    // ─────────────────────────────────────────────

    public function getTenants(string $accessToken): array
    {
        $response = Http::withToken($accessToken)->get(self::IDENTITY_URL);
        $response->throw();

        return $response->json();
    }

    /**
     * Return the single active, reauth-free Xero connection, or null.
     * Callers that need a connection should check for null and redirect
     * the admin to reconnect.
     */
    public function getActiveConnection(?Client $client = null): ?XeroConnection
    {
        return XeroConnection::where('is_active', true)
            ->where('needs_reauth', false)
            ->whereNotNull('access_token')
            ->whereNotNull('refresh_token')
            ->first();
    }

    // ─────────────────────────────────────────────
    // Contact matching
    // ─────────────────────────────────────────────

    public function findMatchingContacts(XeroConnection $connection, Customer $customer): array
    {
        $connection = $this->refreshToken($connection);
        $candidates = [];

        if ($customer->email) {
            $byEmail = $this->searchContacts($connection, $customer->email);
            foreach ($byEmail as $contact) {
                $candidates[$contact['ContactID']] = [
                    'contact' => $contact,
                    'score' => 100,
                    'method' => 'email',
                    'reason' => 'Exact email match',
                ];
            }
        }

        if (! empty($candidates)) {
            return array_values($candidates);
        }

        $searchName = $customer->company_name ?? $customer->name;
        $byName = $this->searchContacts($connection, $searchName);
        foreach ($byName as $contact) {
            $score = $this->similarityScore($searchName, $contact['Name'] ?? '');
            if ($score >= 60) {
                $candidates[$contact['ContactID']] = [
                    'contact' => $contact,
                    'score' => $score,
                    'method' => 'name',
                    'reason' => "Name similarity {$score}%",
                ];
            }
        }

        if ($customer->abn) {
            $byAbn = $this->searchContacts($connection, $customer->abn);
            foreach ($byAbn as $contact) {
                $existing = $candidates[$contact['ContactID']] ?? null;
                $score = 95;
                if ($existing) {
                    $candidates[$contact['ContactID']]['score'] = max($existing['score'], $score);
                    $candidates[$contact['ContactID']]['method'] = 'abn';
                    $candidates[$contact['ContactID']]['reason'] = 'ABN match';
                } else {
                    $candidates[$contact['ContactID']] = [
                        'contact' => $contact,
                        'score' => $score,
                        'method' => 'abn',
                        'reason' => 'ABN match',
                    ];
                }
            }
        }

        usort($candidates, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_values($candidates);
    }

    private function searchContacts(XeroConnection $connection, string $term): array
    {
        $response = Http::withToken($connection->access_token)
            ->withHeaders(['Xero-tenant-id' => $connection->tenant_id])
            ->get(self::BASE_URL.'/Contacts', [
                'searchTerm' => $term,
                'includeArchived' => false,
            ]);

        if ($response->failed()) {
            return [];
        }

        return $response->json('Contacts', []);
    }

    private function similarityScore(string $a, string $b): int
    {
        similar_text(strtolower(trim($a)), strtolower(trim($b)), $percent);

        return (int) round($percent);
    }

    public function createContact(XeroConnection $connection, Client $customer): array
    {
        $connection = $this->refreshToken($connection);

        $payload = [
            'Name' => $customer->company_name ?? $customer->name,
            'EmailAddress' => $customer->billing_email,
            'Phones' => $customer->phone ? [[
                'PhoneType' => 'DEFAULT',
                'PhoneNumber' => $customer->phone,
            ]] : [],
        ];

        $response = Http::withToken($connection->access_token)
            ->withHeaders(['Xero-tenant-id' => $connection->tenant_id])
            ->put(self::BASE_URL.'/Contacts', ['Contacts' => [$payload]]);

        $response->throw();

        return $response->json('Contacts.0');
    }

    // ─────────────────────────────────────────────
    // Invoice & Payment
    // ─────────────────────────────────────────────

    public function getInvoice(XeroConnection $connection, string $invoiceId): array
    {
        $connection = $this->refreshToken($connection);

        $response = Http::withToken($connection->access_token)
            ->withHeaders(['Xero-tenant-id' => $connection->tenant_id])
            ->get(self::BASE_URL."/Invoices/{$invoiceId}");

        $response->throw();

        return $response->json('Invoices.0', []);
    }

    public function getOutstandingInvoices(XeroConnection $connection, string $xeroContactId): array
    {
        $connection = $this->refreshToken($connection);

        $response = Http::withToken($connection->access_token)
            ->withHeaders(['Xero-tenant-id' => $connection->tenant_id])
            ->get(self::BASE_URL.'/Invoices', [
                'ContactIDs' => $xeroContactId,
                'Statuses' => 'AUTHORISED',
                'Type' => 'ACCREC',
            ]);

        if ($response->failed()) {
            return [];
        }

        return $response->json('Invoices', []);
    }

    public function markInvoicePaid(XeroConnection $connection, Payment $payment): bool
    {
        $connection = $this->refreshToken($connection);

        if (! $payment->xero_invoice_id) {
            Log::warning('XeroService: markInvoicePaid called with no xero_invoice_id', [
                'payment_id' => $payment->id,
            ]);

            return false;
        }

        $payload = [
            'Invoice' => ['InvoiceID' => $payment->xero_invoice_id],
            'Account' => ['Code' => config('services.xero.bank_account_code', '090')],
            'Date' => now()->format('Y-m-d'),
            'Amount' => $payment->amount / 100,
            'Reference' => "Stripe BECS – {$payment->stripe_payment_intent_id}",
            'CurrencyRate' => 1.0,
        ];

        $response = Http::withToken($connection->access_token)
            ->withHeaders(['Xero-tenant-id' => $connection->tenant_id])
            ->put(self::BASE_URL.'/Payments', $payload);

        if ($response->failed()) {
            $payment->update([
                'xero_sync_status' => 'failed',
                'xero_sync_error' => $response->body(),
            ]);

            Log::error('XeroService: payment sync failed', [
                'payment_id' => $payment->id,
                'response' => $response->body(),
            ]);

            return false;
        }

        $xeroPayment = $response->json('Payments.0', []);

        $payment->update([
            'xero_payment_id' => $xeroPayment['PaymentID'] ?? null,
            'xero_sync_status' => 'synced',
            'xero_synced_at' => now(),
        ]);

        Log::info('XeroService: invoice marked paid', [
            'payment_id' => $payment->id,
            'xero_invoice_id' => $payment->xero_invoice_id,
            'xero_payment_id' => $xeroPayment['PaymentID'] ?? null,
        ]);

        return true;
    }

    public function getContacts(XeroConnection $connection, XeroTenant $tenant): array
    {
        $connection = $this->refreshToken($connection);

        $response = Http::withToken($connection->access_token)
            ->withHeaders(['Xero-tenant-id' => $tenant->tenant_id])
            ->get(self::BASE_URL.'/Contacts', [
                'status' => 'ACTIVE',
                'isCustomer' => 'true',
            ]);

        return $response->successful()
            ? $response->json('Contacts', [])
            : [];
    }

    public function getContactInvoices(XeroTenant $tenant, string $contactId): array
    {
        $connection = $this->refreshToken($tenant->connection);

        $response = Http::withToken($connection->access_token)
            ->withHeaders(['Xero-tenant-id' => $tenant->tenant_id])
            ->get(self::BASE_URL.'/Invoices', [
                'where' => 'Contact.ContactID=Guid("'.$contactId.'")',
                'Statuses' => 'AUTHORISED',
                'Type' => 'ACCREC',
            ]);

        if ($response->failed()) {
            Log::error('XeroService: contact invoices fetch failed', [
                'contact_id' => $contactId,
                'body' => $response->body(),
            ]);

            return [];
        }

        return $response->json('Invoices', []);
    }

    public function getBankAccounts(XeroTenant $tenant): array
    {
        $connection = $this->refreshToken($tenant->connection);

        $response = Http::withToken($connection->access_token)
            ->withHeaders(['Xero-tenant-id' => $tenant->tenant_id])
            ->get(self::BASE_URL.'/Accounts', ['where' => 'Type=="BANK"']);

        if ($response->failed()) {
            Log::error('XeroService: failed to fetch bank accounts', [
                'tenant_id' => $tenant->tenant_id,
                'body' => $response->body(),
            ]);

            return [];
        }

        return collect($response->json('Accounts', []))
            ->map(fn ($a) => [
                'account_id' => $a['AccountID'],
                'name' => $a['Name'],
                'code' => $a['Code'] ?? null,
            ])
            ->values()
            ->all();
    }

    public function getUserInfo(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get('https://identity.xero.com/connect/userinfo');

        if ($response->failed()) {
            Log::warning('XeroService: could not fetch userinfo', [
                'status' => $response->status(),
            ]);

            return [];
        }

        return $response->json();
    }

    public function getPayment(XeroTenant $tenant, string $paymentId): array
    {
        $connection = $this->refreshToken($tenant->connection);

        $response = Http::withToken($connection->access_token)
            ->withHeaders(['Xero-tenant-id' => $tenant->tenant_id])
            ->get(self::BASE_URL."/Payments/{$paymentId}");

        if ($response->failed()) {
            Log::error('XeroService: failed to fetch payment', [
                'tenant_id' => $tenant->tenant_id,
                'payment_id' => $paymentId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Xero payment fetch failed: '.$response->body());
        }

        return $response->json('Payments.0', []);
    }

    public function getInvoiceForTenant(XeroTenant $tenant, string $invoiceId): array
    {
        $connection = $this->refreshToken($tenant->connection);

        $response = Http::withToken($connection->access_token)
            ->withHeaders(['Xero-tenant-id' => $tenant->tenant_id])
            ->get(self::BASE_URL."/Invoices/{$invoiceId}");

        if ($response->failed()) {
            Log::error('XeroService: failed to fetch invoice', [
                'tenant_id' => $tenant->tenant_id,
                'invoice_id' => $invoiceId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Xero invoice fetch failed: '.$response->body());
        }

        return $response->json('Invoices.0', []);
    }

    // ─────────────────────────────────────────────
    // Write-through CRUD (Xero is source of truth)
    //
    // Conventions (per Xero Accounting API):
    //   - PUT creates, POST creates-or-updates.
    //   - Invoices: DRAFT/SUBMITTED → DELETED, AUTHORISED → VOIDED.
    //   - Contacts: no hard delete — ARCHIVED / ACTIVE restore.
    //   - Repeating templates: DRAFT/AUTHORISED → DELETED.
    // Every write sends an Idempotency-Key so queue retries are safe.
    // ─────────────────────────────────────────────

    /**
     * Low-level tenant-scoped request with uniform error handling.
     *
     * @throws \RuntimeException with Xero's validation detail in the message.
     */
    public function api(XeroTenant $tenant, string $method, string $uri, array $payload = [], ?string $idempotencyKey = null): array
    {
        $connection = $this->refreshToken($tenant->connection);

        $headers = ['Xero-tenant-id' => $tenant->tenant_id];

        if ($idempotencyKey !== null) {
            $headers['Idempotency-Key'] = substr($idempotencyKey, 0, 128);
        }

        $request = Http::withToken($connection->access_token)
            ->withHeaders($headers)
            ->acceptJson();

        $method = strtoupper($method);
        $url = self::BASE_URL.$uri;

        $response = match ($method) {
            'GET' => $request->get($url, $payload),
            'PUT' => $request->put($url, $payload),
            'POST' => $request->post($url, $payload),
            default => throw new \InvalidArgumentException("Unsupported Xero method [{$method}]."),
        };

        if ($response->failed()) {
            Log::error('XeroService: API request failed', [
                'tenant_id' => $tenant->tenant_id,
                'method' => $method,
                'uri' => $uri,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException($this->friendlyError($method, $uri, $response->json(), $response->status()));
        }

        return $response->json() ?? [];
    }

    /** Create a sales invoice / bill (PUT — create-only). */
    public function createInvoice(XeroTenant $tenant, array $invoice, ?string $idempotencyKey = null): array
    {
        $data = $this->api($tenant, 'PUT', '/Invoices', ['Invoices' => [$invoice]], $idempotencyKey ?? (string) Str::uuid());

        return $data['Invoices'][0] ?? [];
    }

    /** Update a DRAFT/SUBMITTED invoice (POST — create-or-update). */
    public function updateInvoice(XeroTenant $tenant, string $invoiceId, array $invoice): array
    {
        $invoice['InvoiceID'] = $invoiceId;
        $data = $this->api($tenant, 'POST', '/Invoices', ['Invoices' => [$invoice]], (string) Str::uuid());

        return $data['Invoices'][0] ?? [];
    }

    /** Soft-delete a DRAFT/SUBMITTED invoice (Status → DELETED). */
    public function deleteInvoice(XeroTenant $tenant, string $invoiceId, ?string $invoiceNumber = null): array
    {
        $payload = ['InvoiceID' => $invoiceId, 'Status' => 'DELETED'];

        if ($invoiceNumber !== null) {
            $payload['InvoiceNumber'] = $invoiceNumber;
        }

        return $this->updateInvoice($tenant, $invoiceId, $payload);
    }

    /** Void an AUTHORISED invoice (Status → VOIDED). */
    public function voidInvoice(XeroTenant $tenant, string $invoiceId, ?string $invoiceNumber = null): array
    {
        $payload = ['InvoiceID' => $invoiceId, 'Status' => 'VOIDED'];

        if ($invoiceNumber !== null) {
            $payload['InvoiceNumber'] = $invoiceNumber;
        }

        return $this->updateInvoice($tenant, $invoiceId, $payload);
    }

    /** Authorise a DRAFT/SUBMITTED invoice (Status → AUTHORISED). */
    public function authoriseInvoice(XeroTenant $tenant, string $invoiceId): array
    {
        return $this->updateInvoice($tenant, $invoiceId, ['Status' => 'AUTHORISED']);
    }

    /** Create a contact (PUT — create-only). Requires Name. */
    public function createContactForTenant(XeroTenant $tenant, array $contact, ?string $idempotencyKey = null): array
    {
        $data = $this->api($tenant, 'PUT', '/Contacts', ['Contacts' => [$contact]], $idempotencyKey ?? (string) Str::uuid());

        return $data['Contacts'][0] ?? [];
    }

    /** Update a contact (POST — partial update; omitted fields preserved). */
    public function updateContactForTenant(XeroTenant $tenant, string $contactId, array $contact): array
    {
        $contact['ContactID'] = $contactId;
        $data = $this->api($tenant, 'POST', '/Contacts', ['Contacts' => [$contact]], (string) Str::uuid());

        return $data['Contacts'][0] ?? [];
    }

    /** Archive a contact (Xero has no hard delete for contacts). */
    public function archiveContact(XeroTenant $tenant, string $contactId): array
    {
        return $this->updateContactForTenant($tenant, $contactId, ['ContactStatus' => 'ARCHIVED']);
    }

    /** Restore an archived contact. */
    public function restoreContact(XeroTenant $tenant, string $contactId): array
    {
        return $this->updateContactForTenant($tenant, $contactId, ['ContactStatus' => 'ACTIVE']);
    }

    /** Fetch a single repeating-invoice template. */
    public function getRepeatingInvoice(XeroTenant $tenant, string $repeatingInvoiceId): array
    {
        $data = $this->api($tenant, 'GET', "/RepeatingInvoices/{$repeatingInvoiceId}");

        return $data['RepeatingInvoices'][0] ?? [];
    }

    /** Create a repeating-invoice template (PUT — DRAFT or AUTHORISED). */
    public function createRepeatingInvoice(XeroTenant $tenant, array $template, ?string $idempotencyKey = null): array
    {
        $data = $this->api($tenant, 'PUT', '/RepeatingInvoices', ['RepeatingInvoices' => [$template]], $idempotencyKey ?? (string) Str::uuid());

        return $data['RepeatingInvoices'][0] ?? [];
    }

    /** Update a repeating-invoice template (POST). */
    public function updateRepeatingInvoice(XeroTenant $tenant, string $repeatingInvoiceId, array $template): array
    {
        $template['RepeatingInvoiceID'] = $repeatingInvoiceId;
        $data = $this->api($tenant, 'POST', '/RepeatingInvoices', ['RepeatingInvoices' => [$template]], (string) Str::uuid());

        return $data['RepeatingInvoices'][0] ?? [];
    }

    /** Delete a repeating-invoice template (Status → DELETED). */
    public function deleteRepeatingInvoice(XeroTenant $tenant, string $repeatingInvoiceId): array
    {
        return $this->updateRepeatingInvoice($tenant, $repeatingInvoiceId, ['Status' => 'DELETED']);
    }

    /** Sales-capable accounts for the invoice line-item picker. */
    public function getSalesAccounts(XeroTenant $tenant): array
    {
        $data = $this->api($tenant, 'GET', '/Accounts', ['where' => 'Type=="SALES"||Type=="REVENUE"']);

        return $data['Accounts'] ?? [];
    }

    /** Tax rates for the invoice line-item picker. */
    public function getTaxRates(XeroTenant $tenant): array
    {
        $data = $this->api($tenant, 'GET', '/TaxRates');

        return $data['TaxRates'] ?? [];
    }

    /** Branding themes for the invoice form picker (best-effort; empty on failure). */
    public function getBrandingThemes(XeroTenant $tenant): array
    {
        try {
            $data = $this->api($tenant, 'GET', '/BrandingThemes');

            return $data['BrandingThemes'] ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Flatten Xero's nested ValidationErrors into a readable message.
     */
    private function friendlyError(string $method, string $uri, ?array $body, int $status): string
    {
        $candidates = [];

        foreach (['Invoices', 'Contacts', 'RepeatingInvoices', 'Payments'] as $key) {
            foreach ((array) ($body[$key] ?? []) as $row) {
                foreach ((array) ($row['ValidationErrors'] ?? []) as $error) {
                    if (! empty($error['Message'])) {
                        $candidates[] = $error['Message'];
                    }
                }
            }
        }

        if (! empty($body['Message'])) {
            $candidates[] = $body['Message'];
        }

        if (! empty($body['Detail'])) {
            $candidates[] = $body['Detail'];
        }

        $detail = $candidates !== [] ? implode(' ', array_unique($candidates)) : 'Unknown Xero error.';

        return "Xero {$method} {$uri} failed [{$status}]: {$detail}";
    }
}
