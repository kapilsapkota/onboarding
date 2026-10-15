<?php

namespace App\Services;

use App\Models\Client;
use App\Models\XeroContact;
use Illuminate\Support\Facades\Log;

class XeroMatchService
{
    /**
     * Auto-match every unmatched contact (optionally scoped to tenants).
     *
     * Only exact-identity methods (email, ABN) at or above the threshold
     * are linked automatically; fuzzy name matches are left as suggestions
     * for an admin to confirm. Returns per-method counts.
     *
     * @param  int[]|null  $tenantIds  Null means every tenant.
     * @return array{linked: int, suggested: int, skipped: int}
     */
    public function autoMatchUnmatched(?int $tenantId = null, int $threshold = 95, ?array $tenantIds = null): array
    {
        $tenantIds ??= $tenantId ? [$tenantId] : null;

        $contacts = XeroContact::query()
            ->unmatched()
            ->when($tenantIds !== null, fn ($query) => $query->whereIn('xero_tenant_id', $tenantIds))
            ->where(fn ($query) => $query->whereNull('xero_contact_status')->orWhere('xero_contact_status', '!=', 'ARCHIVED'))
            ->get();

        $clients = Client::all();
        $linked = 0;
        $suggested = 0;
        $skipped = 0;

        foreach ($contacts as $contact) {
            $match = $this->matchContact($this->toPayload($contact), $clients);

            if (! $match['customer'] || $match['score'] < $threshold || ! in_array($match['method'], ['email', 'abn'], true)) {
                $match['customer'] ? $suggested++ : $skipped++;

                continue;
            }

            $contact->markMatched($match['customer'], (float) $match['score'], 'auto:'.$match['method']);
            $linked++;

            Log::info('Xero contact auto-matched', [
                'xero_contact_id' => $contact->id,
                'client_id' => $match['customer']->id,
                'method' => $match['method'],
            ]);
        }

        return ['linked' => $linked, 'suggested' => $suggested, 'skipped' => $skipped];
    }

    /**
     * Shape a stored contact into the raw Xero payload the matcher expects.
     *
     * @return array<string, mixed>
     */
    public function toPayload(XeroContact $contact): array
    {
        return [
            'ContactID' => $contact->xero_contact_id,
            'Name' => $contact->name,
            'EmailAddress' => $contact->email,
            'TaxNumber' => $contact->tax_number,
        ];
    }

    public function matchContact(array $contact, $customers): array
    {
        $bestMatch = null;
        $bestScore = 0;
        $contactEmail = strtolower($contact['EmailAddress'] ?? '');
        $contactName = strtolower($contact['Name'] ?? '');

        foreach ($customers as $customer) {
            $score = 0;

            // 1. EMAIL MATCH (strongest)
            if ($contactEmail && strtolower(trim($customer->billing_email)) === $contactEmail) {
                return [
                    'customer' => $customer,
                    'score' => 100,
                    'method' => 'email',
                ];
            }

            // 2. ABN MATCH
            if (! empty($customer->abn) && isset($contact['TaxNumber'])) {
                if ($customer->abn === $contact['TaxNumber']) {
                    return [
                        'customer' => $customer,
                        'score' => 95,
                        'method' => 'abn',
                    ];
                }
            }

            // 3. NAME SIMILARITY
            similar_text(
                strtolower($customer->company_name),
                $contactName,
                $percent
            );

            $score = (int) $percent;

            if ($score > $bestScore && $score >= 60) {
                $bestScore = $score;
                $bestMatch = $customer;
            }
        }

        return $bestMatch
            ? [
                'customer' => $bestMatch,
                'score' => $bestScore,
                'method' => 'name',
            ]
            : [
                'customer' => null,
                'score' => 0,
                'method' => 'none',
            ];
    }
}
