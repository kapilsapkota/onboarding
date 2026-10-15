<?php

namespace App\Services;

use App\Models\XeroConnection;
use App\Models\XeroContact;
use App\Models\XeroInvoice;
use App\Models\XeroRepeatingInvoice;
use App\Models\XeroTenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XeroRepeatingSyncService
{
    private const BASE_URL = 'https://api.xero.com/api.xro/2.0';

    public function __construct(private XeroService $xero) {}

    /**
     * Full pull of all repeating-invoice templates for a tenant.
     * Throws on HTTP failure so the job can retry.
     */
    public function sync(XeroConnection $connection, XeroTenant $tenant): array
    {
        $connection = $this->xero->refreshToken($connection);

        $response = Http::withToken($connection->access_token)
            ->withHeaders(['Xero-tenant-id' => $tenant->tenant_id])
            ->get(self::BASE_URL.'/RepeatingInvoices');

        if ($response->failed()) {
            Log::error('XeroRepeatingSyncService: fetch failed', [
                'tenant_id' => $tenant->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Xero RepeatingInvoices fetch failed [{$response->status()}]");
        }

        $templates = $response->json('RepeatingInvoices', []);

        Log::info('XeroRepeatingSyncService: fetched templates', [
            'tenant_id' => $tenant->id,
            'count' => count($templates),
        ]);

        $synced = 0;
        $failed = 0;

        DB::transaction(function () use ($templates, $tenant, &$synced, &$failed) {
            foreach ($templates as $raw) {
                try {
                    $repeating = $this->upsertTemplate($raw, $tenant);
                    $this->linkGeneratedInvoices($repeating, $tenant);
                    $synced++;
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('XeroRepeatingSyncService: failed to upsert template', [
                        'repeating_invoice_id' => $raw['RepeatingInvoiceID'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        $tenant->update(['last_repeating_invoice_synced_at' => now()]);

        return ['synced' => $synced, 'failed' => $failed];
    }

    /**
     * Fetch and upsert a single template by its Xero UUID.
     * Used for write-through after create/update via the API.
     */
    public function syncOne(XeroConnection $connection, XeroTenant $tenant, string $xeroRepeatingInvoiceId): XeroRepeatingInvoice
    {
        $connection = $this->xero->refreshToken($connection);

        $response = Http::withToken($connection->access_token)
            ->withHeaders(['Xero-tenant-id' => $tenant->tenant_id])
            ->get(self::BASE_URL.'/RepeatingInvoices/'.$xeroRepeatingInvoiceId);

        if ($response->failed()) {
            Log::error('XeroRepeatingSyncService: single template fetch failed', [
                'repeating_invoice_id' => $xeroRepeatingInvoiceId,
                'tenant_id' => $tenant->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Xero repeating invoice fetch failed [{$response->status()}]");
        }

        $templates = $response->json('RepeatingInvoices', []);

        if (empty($templates)) {
            throw new \RuntimeException("Xero repeating invoice {$xeroRepeatingInvoiceId} not found.");
        }

        $repeating = DB::transaction(function () use ($templates, $tenant) {
            $repeating = $this->upsertTemplate($templates[0], $tenant);
            $this->linkGeneratedInvoices($repeating, $tenant);

            return $repeating;
        });

        $tenant->update(['last_repeating_invoice_synced_at' => now()]);

        return $repeating;
    }

    /**
     * Upsert a template from a raw API payload (also used for
     * write-through directly from create/update responses).
     */
    public function upsertTemplate(array $raw, XeroTenant $tenant): XeroRepeatingInvoice
    {
        $xeroContactXeroId = $raw['Contact']['ContactID'] ?? null;
        $localContactId = $xeroContactXeroId
            ? XeroContact::where('xero_contact_id', $xeroContactXeroId)->value('id')
            : null;

        $schedule = $raw['Schedule'] ?? [];

        return XeroRepeatingInvoice::updateOrCreate(
            ['xero_repeating_invoice_id' => $raw['RepeatingInvoiceID']],
            [
                'xero_tenant_id' => $tenant->id,
                'type' => $raw['Type'] ?? null,
                'status' => $raw['Status'] ?? null,

                'xero_contact_id' => $localContactId,
                'xero_contact_xero_id' => $xeroContactXeroId,

                'schedule_period' => $schedule['Period'] ?? null,
                'schedule_period_type' => $schedule['Unit'] ?? null,
                'schedule_due_date' => $schedule['DueDate'] ?? null,
                'schedule_due_date_type' => $schedule['DueDateType'] ?? null,
                'schedule_start_date' => $this->parseDate($schedule['StartDate'] ?? null),
                'schedule_next_scheduled_date' => $this->parseDate($schedule['NextScheduledDate'] ?? null),
                'schedule_end_date' => $this->parseDate($schedule['EndDate'] ?? null),

                'currency_code' => $raw['CurrencyCode'] ?? null,
                'sub_total' => $raw['SubTotal'] ?? null,
                'total_tax' => $raw['TotalTax'] ?? null,
                'total' => $raw['Total'] ?? null,
                'line_items' => $this->serializeLineItems($raw['LineItems'] ?? []),
                'reference' => $raw['Reference'] ?? null,
                'xero_branding_theme_id' => $raw['BrandingThemeID'] ?? null,
                'has_attachments' => (bool) ($raw['HasAttachments'] ?? false),
                'last_synced_at' => now(),
            ]
        );
    }

    /**
     * Link existing XeroInvoice rows back to this repeating template.
     *
     * Xero does NOT include a RepeatingInvoiceID on generated invoices —
     * so we match on: same tenant + same contact + same total + invoice
     * date falls on or after the template's start date.
     *
     * This is a best-effort heuristic. Edge cases (amount changed mid-way,
     * multiple templates for the same contact+amount) are handled by only
     * updating rows where xero_repeating_invoice_id IS NULL, so a manually
     * confirmed link is never overwritten.
     */
    public function linkGeneratedInvoices(XeroRepeatingInvoice $repeating, XeroTenant $tenant): void
    {
        if (! $repeating->xero_contact_xero_id || ! $repeating->total) {
            return;
        }

        XeroInvoice::query()
            ->where('xero_tenant_id', $tenant->id)
            ->where('xero_contact_xero_id', $repeating->xero_contact_xero_id)
            ->where('type', $repeating->type)
            ->whereNull('xero_repeating_invoice_id')
            ->where('total', $repeating->total)
            ->when($repeating->schedule_start_date, fn ($q) => $q->where('invoice_date', '>=', $repeating->schedule_start_date))
            ->update(['xero_repeating_invoice_id' => $repeating->id]);
    }

    private function parseDate(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (preg_match('#/Date\((\d+)[+-]?\d*\)/#', $value, $m)) {
            return Carbon::createFromTimestampMs((int) $m[1], 'UTC')->toDateString();
        }

        return Carbon::parse($value)->toDateString();
    }

    private function serializeLineItems(array $items): ?array
    {
        if (empty($items)) {
            return null;
        }

        return array_map(fn (array $item) => [
            'line_item_id' => $item['LineItemID'] ?? null,
            'description' => $item['Description'] ?? null,
            'quantity' => $item['Quantity'] ?? null,
            'unit_amount' => $item['UnitAmount'] ?? null,
            'account_code' => $item['AccountCode'] ?? null,
            'tax_type' => $item['TaxType'] ?? null,
            'line_amount' => $item['LineAmount'] ?? null,
        ], $items);
    }
}
