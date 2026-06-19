<?php


namespace App\Services;

use App\Models\Client;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\XeroConnection;
use App\Models\XeroTenant;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Auth;
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
    )
    {
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

        return self::AUTHORIZE_URL . '?' . $params;
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
    public function refreshToken(XeroConnection $connection): XeroConnection
    {
        if ($connection->token_expires_at && $connection->token_expires_at->gt(now()->addMinutes(5))) {
            return $connection;
        }

        return Cache::lock('xero-refresh-' . $connection->id, 10)->get(function () use ($connection) {
            $connection = $connection->fresh();

            if ($connection->token_expires_at && $connection->token_expires_at->gt(now()->addMinutes(5))) {
                return $connection;
            }

            Log::info('Refreshing Xero token', ['tenant_id' => $connection->tenant_id]);

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
                ]);

                return $connection;

            } catch (RequestException $e) {
                if ($e->response->status() === 400 || $e->response->status() === 401) {
                    Log::error('Xero refresh token is invalid or revoked.', [
                        'connection_id' => $connection->id,
                        'response' => $e->response->body()
                    ]);

                    $connection->update(['is_active' => false]);
                }

                throw $e;
            }
        });
    }


    public function getTenants(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get(self::IDENTITY_URL);

        $response->throw();

        return $response->json();
    }

    // ─────────────────────────────────────────────
    // Contact matching
    // ─────────────────────────────────────────────

    /**
     * Attempt to find a Xero contact matching a customer.
     * Returns array of candidates with a match confidence score.
     */
    public function findMatchingContacts(XeroConnection $connection, Customer $customer): array
    {
        $connection = $this->refreshToken($connection);

        $candidates = [];

        // 1. Exact email match
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

        if (!empty($candidates)) {
            return array_values($candidates);
        }

        // 2. Name match (company name preferred, fall back to individual name)
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

        // 3. ABN match if available
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

        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_values($candidates);
    }

    private function searchContacts(XeroConnection $connection, string $term): array
    {
        $response = Http::withToken($connection->access_token)
            ->withHeaders(['Xero-tenant-id' => $connection->tenant_id])
            ->get(self::BASE_URL . '/Contacts', [
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
        similar_text(
            strtolower(trim($a)),
            strtolower(trim($b)),
            $percent
        );

        return (int)round($percent);
    }

    /**
     * Create a new Xero contact from a Customer record.
     */
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
            ->put(self::BASE_URL . '/Contacts', ['Contacts' => [$payload]]);

        $response->throw();

        return $response->json('Contacts.0');
    }

    // ─────────────────────────────────────────────
    // Invoice & Payment
    // ─────────────────────────────────────────────

    /**
     * Fetch an invoice from Xero by its ID.
     */
    public function getInvoice(XeroConnection $connection, string $invoiceId): array
    {
        $connection = $this->refreshToken($connection);

        $response = Http::withToken($connection->access_token)
            ->withHeaders(['Xero-tenant-id' => $connection->tenant_id])
            ->get(self::BASE_URL . "/Invoices/{$invoiceId}");

        $response->throw();

        return $response->json('Invoices.0', []);
    }

    /**
     * Fetch all outstanding (AUTHORISED) invoices for a Xero contact.
     */
    public function getOutstandingInvoices(XeroConnection $connection, string $xeroContactId): array
    {
        $connection = $this->refreshToken($connection);

        $response = Http::withToken($connection->access_token)
            ->withHeaders(['Xero-tenant-id' => $connection->tenant_id])
            ->get(self::BASE_URL . '/Invoices', [
                'ContactIDs' => $xeroContactId,
                'Statuses' => 'AUTHORISED',
                'Type' => 'ACCREC',
            ]);

        if ($response->failed()) {
            return [];
        }

        return $response->json('Invoices', []);
    }

    /**
     * Mark a Xero invoice as paid after a successful Stripe charge.
     */
    public function markInvoicePaid(XeroConnection $connection, Payment $payment): bool
    {
        $connection = $this->refreshToken($connection);

        if (!$payment->xero_invoice_id) {
            Log::warning('markInvoicePaid: no xero_invoice_id', ['payment_id' => $payment->id]);
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
            ->put(self::BASE_URL . '/Payments', $payload);

        if ($response->failed()) {
            $payment->update([
                'xero_sync_status' => 'failed',
                'xero_sync_error' => $response->body(),
            ]);
            Log::error('Xero payment sync failed', [
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

        Log::info('Xero invoice marked paid', [
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
            ->withHeaders([
                'Xero-tenant-id' => $tenant->tenant_id,
            ])
            ->get(self::BASE_URL . '/Contacts',
            [
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
            ->withHeaders([
                'Xero-tenant-id' => $tenant->tenant_id,
            ])
            ->get(self::BASE_URL . '/Invoices', [
                'where' => 'Contact.ContactID=Guid("' . $contactId . '")',
                'Statuses' => 'AUTHORISED',
                'Type' => 'ACCREC',
            ]);

        if ($response->failed()) {
            Log::error('Xero contact invoices failed', [
                'contact_id' => $contactId,
                'body' => $response->body(),
            ]);

            return [];
        }

        return $response->json('Invoices', []);
    }
    public function getActiveConnection(Client $client): ?XeroConnection
    {
        return XeroConnection::where('user_id', Auth::id())
            ->whereNotNull('access_token')
            ->first();
    }

    /**
     * Returns all BANK type accounts for the given tenant.
     * Each item: ['account_id' => uuid, 'name' => string, 'code' => string]
     */
    public function getBankAccounts(XeroTenant $tenant): array
    {
        $connection = $this->refreshToken($tenant->connection);

        $response = Http::withToken($connection->access_token)
            ->withHeaders([
                'Xero-tenant-id' => $tenant->tenant_id,
            ])
            ->get(self::BASE_URL . '/Accounts', [
                'where' => 'Type=="BANK"',
            ]);

        if ($response->failed()) {
            Log::error('XeroService: failed to fetch bank accounts', [
                'tenant_id' => $tenant->tenant_id,
                'body'      => $response->body(),
            ]);
            return [];
        }

        return collect($response->json('Accounts', []))
            ->map(fn ($a) => [
                'account_id' => $a['AccountID'],
                'name'       => $a['Name'],
                'code'       => $a['Code'] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * Fetch the authenticated Xero user's profile from the userinfo endpoint.
     * Use this on token refresh since refresh grants don't return a new id_token.
     */
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
}
