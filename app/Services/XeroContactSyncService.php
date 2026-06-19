<?php

namespace App\Services;

use App\Models\Client;
use App\Models\XeroConnection;
use App\Models\XeroContact;
use App\Models\XeroTenant;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XeroContactSyncService
{
    private const BASE_URL = 'https://api.xero.com/api.xro/2.0';

    public function __construct(
        private XeroService $xero,
        private XeroMatchService $matcher,
    ) {}

    public function sync(XeroConnection $connection, XeroTenant $tenant): array
    {
        $connection = $this->xero->refreshToken($connection);

        $clients = Client::all();

        $page   = 1;
        $synced = 0;
        $failed = 0;

        do {
            $response = Http::withToken($connection->access_token)
                ->withHeaders(['Xero-tenant-id' => $tenant->tenant_id])
                ->get(self::BASE_URL . '/Contacts', ['page' => $page]);

            if ($response->failed()) {
                Log::error('Xero contact sync failed', [
                    'tenant_id' => $tenant->id,
                    'status'    => $response->status(),
                    'body'      => $response->body(),
                ]);
                break;
            }

            $contacts = $response->json('Contacts', []);

            if (empty($contacts)) {
                break;
            }

            DB::transaction(function () use ($contacts, $tenant, $clients, &$synced, &$failed) {
                foreach ($contacts as $raw) {
                    try {
                        $xeroContact = $this->upsertContact($raw, $tenant);
//                        $this->runMatching($xeroContact, $raw, $clients);
                        $synced++;
                    } catch (\Throwable $e) {
                        $failed++;
                        Log::warning('Failed to upsert Xero contact', [
                            'contact_id' => $raw['ContactID'] ?? null,
                            'error'      => $e->getMessage(),
                        ]);
                    }
                }
            });

            $page++;

        } while (true);

        $tenant->update(['last_contact_synced_at' => now()]);

        return [
            'synced' => $synced,
            'failed' => $failed,
        ];
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function upsertContact(array $contact, XeroTenant $tenant): XeroContact
    {
        return XeroContact::updateOrCreate(
            [
                'xero_tenant_id'  => $tenant->id,
                'xero_contact_id' => $contact['ContactID'],
            ],
            [
                'xero_contact_number' => $contact['ContactNumber'] ?? null,
                'xero_account_number' => $contact['AccountNumber'] ?? null,
                'xero_contact_status' => $contact['ContactStatus'] ?? null,

                'name'       => $contact['Name'] ?? null,
                'first_name' => $contact['FirstName'] ?? null,
                'last_name'  => $contact['LastName'] ?? null,
                'email'      => $contact['EmailAddress'] ?? null,

                'phone' => $contact['Phones'][0]['PhoneNumber'] ?? null,

                'addresses' => $contact['Addresses'] ?? [],
                'phones'    => $contact['Phones'] ?? [],

                'is_supplier' => $contact['IsSupplier'] ?? false,
                'is_customer' => $contact['IsCustomer'] ?? false,

                'default_currency' => $contact['DefaultCurrency'] ?? null,

                'xero_updated_at' => $this->parseXeroDate($contact['UpdatedDateUTC'] ?? null),

                'synced_at' => now(),
            ]
        );
    }

    private function parseXeroDate(?string $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        // /Date(ms+offset)/ or /Date(ms)/
        if (preg_match('#/Date\((\d+)[+-]\d{4}\)/#', $value, $m)
            || preg_match('#/Date\((\d+)\)/#', $value, $m)) {
            return Carbon::createFromTimestampMs((int) $m[1], 'UTC');
        }

        // Fallback for ISO strings returned by newer Xero endpoints
        return Carbon::parse($value);
    }

    /**
     * Run the matcher and, if successful, record the match on the XeroContact.
     * XeroMatchService must return ['client' => Client, 'score' => float, 'method' => string]
     * or null when no match is found.
     */
//    private function runMatching(XeroContact $xeroContact, array $raw, Collection $clients): void
//    {
//        $result = $this->matcher->matchContact($raw, $clients);
//
//        if ($result === null) {
//            return;
//        }
//        $xeroContact->markMatched(
//            $result['customer'],
//            $result['score'],
//            $result['method'],
//        );
//    }
}
