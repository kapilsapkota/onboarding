<?php

namespace App\Jobs;

use App\Models\XeroConnection;
use App\Models\XeroContact;
use App\Models\XeroInvoice;
use App\Models\XeroRepeatingInvoice;
use App\Models\XeroTenant;
use App\Services\XeroService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncXeroRepeatingInvoices implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const BASE_URL = 'https://api.xero.com/api.xro/2.0';

    public function __construct(
        public int $connectionId,
        public int $tenantId,
    ) {}

    public function handle(XeroService $xero): void
    {
        $connection = XeroConnection::findOrFail($this->connectionId);
        $tenant     = XeroTenant::findOrFail($this->tenantId);

        $connection = $xero->refreshToken($connection);

        $response = Http::withToken($connection->access_token)
            ->withHeaders(['Xero-tenant-id' => $tenant->tenant_id])
            ->get(self::BASE_URL . '/RepeatingInvoices');

        if ($response->failed()) {
            Log::error('SyncXeroRepeatingInvoices: fetch failed', [
                'tenant_id' => $tenant->id,
                'status'    => $response->status(),
                'body'      => $response->body(),
            ]);
            throw new \RuntimeException("Xero RepeatingInvoices fetch failed [{$response->status()}]");
        }

        $templates = $response->json('RepeatingInvoices', []);

        Log::info('SyncXeroRepeatingInvoices: fetched templates', [
            'tenant_id' => $tenant->id,
            'count'     => count($templates),
        ]);

        DB::transaction(function () use ($templates, $tenant) {
            foreach ($templates as $raw) {
                try {
                    $repeating = $this->upsertTemplate($raw, $tenant);
                    $this->linkGeneratedInvoices($repeating, $tenant);
                } catch (\Throwable $e) {
                    Log::warning('SyncXeroRepeatingInvoices: failed to upsert template', [
                        'repeating_invoice_id' => $raw['RepeatingInvoiceID'] ?? null,
                        'error'                => $e->getMessage(),
                    ]);
                }
            }
        });

        $tenant->update(['last_repeating_invoice_synced_at' => now()]);
    }

    // -------------------------------------------------------------------------

    private function upsertTemplate(array $raw, XeroTenant $tenant): XeroRepeatingInvoice
    {
        $xeroContactXeroId = $raw['Contact']['ContactID'] ?? null;
        $localContactId    = $xeroContactXeroId
            ? XeroContact::where('xero_contact_id', $xeroContactXeroId)->value('id')
            : null;

        $schedule = $raw['Schedule'] ?? [];

        return XeroRepeatingInvoice::updateOrCreate(
            ['xero_repeating_invoice_id' => $raw['RepeatingInvoiceID']],
            [
                'xero_tenant_id'         => $tenant->id,
                'type'                   => $raw['Type'] ?? null,
                'status'                 => $raw['Status'] ?? null,

                'xero_contact_id'        => $localContactId,
                'xero_contact_xero_id'   => $xeroContactXeroId,

                'schedule_period'              => $schedule['Period'] ?? null,
                'schedule_period_type'         => $schedule['Unit'] ?? null,
                'schedule_due_date'            => $schedule['DueDate'] ?? null,
                'schedule_due_date_type'       => $schedule['DueDateType'] ?? null,
                'schedule_start_date'          => $this->parseDate($schedule['StartDate'] ?? null),
                'schedule_next_scheduled_date' => $this->parseDate($schedule['NextScheduledDate'] ?? null),
                'schedule_end_date'            => $this->parseDate($schedule['EndDate'] ?? null),

                'currency_code'          => $raw['CurrencyCode'] ?? null,
                'sub_total'              => $raw['SubTotal'] ?? null,
                'total_tax'              => $raw['TotalTax'] ?? null,
                'total'                  => $raw['Total'] ?? null,
                'line_items'             => $this->serializeLineItems($raw['LineItems'] ?? []),
                'reference'              => $raw['Reference'] ?? null,
                'xero_branding_theme_id' => $raw['BrandingThemeID'] ?? null,
                'has_attachments'        => (bool) ($raw['HasAttachments'] ?? false),
                'last_synced_at'         => now(),
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
    private function linkGeneratedInvoices(XeroRepeatingInvoice $repeating, XeroTenant $tenant): void
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
            ->when($repeating->schedule_start_date, fn ($q) =>
                $q->where('invoice_date', '>=', $repeating->schedule_start_date)
            )
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
            'description'  => $item['Description'] ?? null,
            'quantity'     => $item['Quantity'] ?? null,
            'unit_amount'  => $item['UnitAmount'] ?? null,
            'account_code' => $item['AccountCode'] ?? null,
            'tax_type'     => $item['TaxType'] ?? null,
            'line_amount'  => $item['LineAmount'] ?? null,
        ], $items);
    }
}
