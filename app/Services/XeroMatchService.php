<?php
namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\Log;

class XeroMatchService
{
    public function matchContact(array $contact, $customers): array
    {
        $bestMatch = null;
        $bestScore = 0;
        $contactEmail = strtolower($contact['EmailAddress'] ?? '');
        $contactName  = strtolower($contact['Name'] ?? '');

        foreach ($customers as $customer) {
            $score = 0;

            // 1. EMAIL MATCH (strongest)
            if ($contactEmail && strtolower(trim($customer->billing_email)) === $contactEmail) {
                return [
                    'customer' => $customer,
                    'score' => 100,
                    'method' => 'email'
                ];
            }

            // 2. ABN MATCH
            if (!empty($customer->abn) && isset($contact['TaxNumber'])) {
                if ($customer->abn === $contact['TaxNumber']) {
                    return [
                        'customer' => $customer,
                        'score' => 95,
                        'method' => 'abn'
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
                'method' => 'name'
            ]
            : [
                'customer' => null,
                'score' => 0,
                'method' => 'none'
            ];
    }
}
