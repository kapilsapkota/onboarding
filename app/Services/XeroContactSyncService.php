<?php

namespace App\Services;

use App\Models\XeroConnection;
use App\Models\XeroTenant;
use App\Models\XeroContact;
use App\Models\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XeroContactSyncService
{
    private const BASE_URL = 'https://api.xero.com/api.xro/2.0';

    public function __construct(
        private XeroService $xero,
        private XeroMatchService $matcher
    ) {}

    public function sync(XeroConnection $connection, XeroTenant $tenant): array
    {
        $connection = $this->xero->refreshToken($connection);

        $page = 1;
        $synced = 0;

        do {
            $response = Http::withToken($connection->access_token)
                ->withHeaders([
                    'Xero-tenant-id' => $tenant->tenant_id,
                ])
                ->get(self::BASE_URL . '/Contacts', [
                    'page' => $page,
                ]);

            if ($response->failed()) {
                Log::error('Xero contact sync failed', [
                    'tenant_id' => $tenant->id,
                    'body' => $response->body(),
                ]);
                break;
            }

            $contacts = $response->json('Contacts', []);

            if (empty($contacts)) {
                break;
            }

            foreach ($contacts as $contact) {

                $xeroContact = XeroContact::updateOrCreate(
                    [
                        'xero_tenant_id' => $tenant->id,
                        'xero_contact_id' => $contact['ContactID'],
                    ],
                    [
                        'xero_contact_number' => $contact['ContactNumber'] ?? null,
                        'xero_account_number' => $contact['AccountNumber'] ?? null,
                        'xero_contact_status' => $contact['ContactStatus'] ?? null,

                        'name' => $contact['Name'] ?? null,
                        'first_name' => $contact['FirstName'] ?? null,
                        'last_name' => $contact['LastName'] ?? null,
                        'email' => $contact['EmailAddress'] ?? null,
                        'phone' => $contact['Phones'][0]['PhoneNumber'] ?? null,

                        'addresses' => $contact['Addresses'] ?? [],
                        'phones' => $contact['Phones'] ?? [],

                        'is_supplier' => $contact['IsSupplier'] ?? false,
                        'is_customer' => $contact['IsCustomer'] ?? false,

                        'default_currency' => $contact['DefaultCurrency'] ?? null,

                        'xero_updated_at' => isset($contact['UpdatedDateUTC'])
                            ? now()->parse($contact['UpdatedDateUTC'])
                            : null,

                        'synced_at' => now(),
                    ]
                );

                /**
                 * 🔥 AUTO MATCHING STEP (CORE LOGIC)
                 */
                $client = $this->matcher->matchContact($contact, Client::all());

                if ($client) {
                    $xeroContact->markMatched(
                        $client,
                        $client->match_score ?? 100,
                        $client->match_method ?? 'auto'
                    );
                }

                $synced++;
            }

            $page++;

        } while (count($contacts) > 0);

        $tenant->update([
            'last_contact_sync_at' => now(),
        ]);

        return [
            'synced' => $synced,
        ];
    }
}
