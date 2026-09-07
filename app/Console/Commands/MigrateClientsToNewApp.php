<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class MigrateClientsToNewApp extends Command
{
    protected $signature = 'migrate:clients
                            {--id= : Migrate only this client ID}
                            {--dry-run : Do not send anything}';

    protected $description = 'Migrate clients and contacts to the new application';

    public function handle(): int
    {
        $url = config('services.new_app.url');
        $token = config('services.new_app.token');

        if (!$url) {
            $this->error('NEW_APP_URL is not configured.');

            return self::FAILURE;
        }

        if (!$token) {
            $this->error('NEW_APP_MIGRATION_TOKEN is not configured.');

            return self::FAILURE;
        }

        $query = Client::with('contacts')
            ->orderBy('id');

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        $clients = $query->get();

        if ($clients->isEmpty()) {
            $this->warn('No clients found.');

            return self::SUCCESS;
        }

        $this->info("Found {$clients->count()} client(s).");

        $success = 0;
        $failed = 0;

        foreach ($clients as $client) {

            $this->newLine();

            $this->info(
                "Migrating #{$client->id} - {$client->company_name}"
            );

            $payload = [
                'source_id' => $client->id,

                'client' => [
                    'company_name'       => $client->company_name,
                    'industry'           => $client->industry,
                    'website'            => $client->website,

                    'address'            => $client->address,
                    'address_second'     => $client->address_second,
                    'city'               => $client->city,
                    'state'              => $client->state,
                    'country'            => $client->country,
                    'post_code'          => $client->post_code,

                    'abn'                => $client->abn,
                    'billing_email'      => $client->billing_email,
                    'monthly_budget'     => $client->monthly_budget,
                    'referred_by'        => $client->referred_by,

                    'instagram'          => $client->instagram,
                    'facebook'           => $client->facebook,
                    'tiktok'             => $client->tiktok,
                    'linkedin'           => $client->linkedin,
                    'twitter'            => $client->twitter,

                    'whatsapp_group'     => $client->whatsapp_group,

                    'logo_path'          => $client->logo_path,
                    'contacts_file_path' => $client->contacts_file_path,
                    'pasted_employees'   => $client->pasted_employees,

                    'notes'              => $client->notes,
                    'status'             => $client->status,
                    'company_phone'      => $client->company_phone,

                    'service_providers'  => $client->service_providers,
                    'services'           => $client->services,

                    'bank_name'          => $client->bank_name,
                    'bank_branch'        => $client->bank_branch,
                    'account_name'       => $client->account_name,
                    'account_number'     => $client->account_number,
                    'bsb'                => $client->bsb,
                ],

                'contacts' => $client->contacts
                    ->map(function ($contact) {

                        return [
                            'source_id'    => $contact->id,

                            'full_name'    => $contact->full_name,
                            'role'         => $contact->role,
                            'contact_type' => $contact->contact_type,

                            'email'       => $contact->email,
                            'phone'       => $contact->phone,
                            'whatsapp'    => $contact->whatsapp,

                            'linkedin_url' => $contact->linkedin_url,

                            'birthday'     => $contact->birthday?->format('Y-m-d'),

                            'email_opt_in' => $contact->email_opt_in,
                            'sms_opt_in'   => $contact->sms_opt_in,

                            'is_primary'   => $contact->is_primary,
                        ];

                    })
                    ->values()
                    ->all(),
            ];

            if ($this->option('dry-run')) {

                $this->line(
                    '  Contacts: ' . count($payload['contacts'])
                );

                $this->line('  ✓ Dry run');

                continue;
            }

            try {

                $response = Http::timeout(60)
                    ->withToken($token)
                    ->acceptJson()
                    ->post(
                        rtrim($url, '/') .
                        '/api/internal/migration/clients',
                        $payload
                    );

                if ($response->successful()) {

                    $this->info(
                        "  ✓ Client #{$client->id} migrated"
                    );

                    $success++;

                } else {

                    $failed++;

                    $this->error(
                        "  ✗ Client #{$client->id} failed"
                    );

                    $this->error(
                        '  HTTP: ' . $response->status()
                    );

                    $this->error(
                        '  ' . $response->body()
                    );
                }

            } catch (Throwable $e) {

                $failed++;

                $this->error(
                    "  ✗ Client #{$client->id} exception"
                );

                $this->error(
                    '  ' . $e->getMessage()
                );
            }
        }

        $this->newLine();

        $this->table(
            ['Result', 'Count'],
            [
                ['Successful', $success],
                ['Failed', $failed],
            ]
        );

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
