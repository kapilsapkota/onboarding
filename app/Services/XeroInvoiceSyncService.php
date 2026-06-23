<?php

namespace App\Services;

use App\Models\XeroConnection;
use App\Models\XeroContact;
use App\Models\XeroInvoice;
use App\Models\XeroTenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XeroInvoiceSyncService
{
    private const BASE_URL  = 'https://api.xero.com/api.xro/2.0';
    private const PAGE_SIZE = 100;

    public function __construct(private XeroService $xero) {}

    /**
     * Paginated bulk sync. Throws on HTTP failure so the job can retry.
     *
     * @param string|null $modifiedAfter ISO-8601 – only fetch invoices updated
     *                                   after this timestamp. Null = full pull.
     */
    public function sync(
        XeroConnection $connection,
        XeroTenant     $tenant,
        ?string        $modifiedAfter = null,
    ): array {
        $connection = $this->xero->refreshToken($connection);

        $page   = 1;
        $synced = 0;
        $failed = 0;

        do {
            $headers = ['Xero-tenant-id' => $tenant->tenant_id];

            if ($modifiedAfter !== null) {
                $headers['If-Modified-Since'] = $modifiedAfter;
            }

            $response = Http::withToken($connection->access_token)
                ->withHeaders($headers)
                ->get(self::BASE_URL . '/Invoices', [
                    'page'     => $page,
                    'pageSize' => self::PAGE_SIZE,
                ]);

            if ($response->failed()) {
                Log::error('XeroInvoiceSyncService: page fetch failed', [
                    'tenant_id' => $tenant->id,
                    'page'      => $page,
                    'status'    => $response->status(),
                    'body'      => $response->body(),
                ]);

                // Throw so the job retries the whole sync rather than silently
                // returning partial results and advancing last_invoice_synced_at.
                throw new \RuntimeException(
                    "Xero invoice page {$page} fetch failed [{$response->status()}]"
                );
            }

            $invoices = $response->json('Invoices', []);

            if (empty($invoices)) {
                break;
            }

            DB::transaction(function () use ($invoices, $tenant, &$synced, &$failed) {
                foreach ($invoices as $raw) {
                    try {
                        $this->upsertInvoice($raw, $tenant);
                        $synced++;
                    } catch (\Throwable $e) {
                        $failed++;
                        Log::warning('XeroInvoiceSyncService: upsert failed', [
                            'invoice_id' => $raw['InvoiceID'] ?? null,
                            'error'      => $e->getMessage(),
                        ]);
                    }
                }
            });

            $page++;

        } while (count($invoices) === self::PAGE_SIZE);

        return ['synced' => $synced, 'failed' => $failed];
    }

    /**
     * Fetch and upsert a single invoice by its Xero UUID.
     * Called from webhook handlers — no pagination needed.
     */
    public function syncOne(
        XeroConnection $connection,
        XeroTenant     $tenant,
        string         $xeroInvoiceId,
    ): void {
        $connection = $this->xero->refreshToken($connection);

        $response = Http::withToken($connection->access_token)
            ->withHeaders(['Xero-tenant-id' => $tenant->tenant_id])
            ->get(self::BASE_URL . '/Invoices/' . $xeroInvoiceId);

        if ($response->failed()) {
            Log::error('XeroInvoiceSyncService: single invoice fetch failed', [
                'xero_invoice_id' => $xeroInvoiceId,
                'tenant_id'       => $tenant->id,
                'status'          => $response->status(),
                'body'            => $response->body(),
            ]);

            throw new \RuntimeException(
                "Xero invoice fetch failed [{$response->status()}]: {$response->body()}"
            );
        }

        $invoices = $response->json('Invoices', []);

        if (empty($invoices)) {
            Log::warning('XeroInvoiceSyncService: single invoice fetch returned empty', [
                'xero_invoice_id' => $xeroInvoiceId,
                'tenant_id'       => $tenant->id,
            ]);
            return;
        }

        $this->upsertInvoice($invoices[0], $tenant);
    }

    // -------------------------------------------------------------------------

    private function upsertInvoice(array $raw, XeroTenant $tenant): XeroInvoice
    {
        $xeroContactXeroId = $raw['Contact']['ContactID'] ?? null;
        $localContactId    = $xeroContactXeroId
            ? XeroContact::where('xero_contact_id', $xeroContactXeroId)->value('id')
            : null;

        return XeroInvoice::updateOrCreate(
            ['xero_invoice_id' => $raw['InvoiceID']],
            [
                'xero_tenant_id'         => $tenant->id,
                'xero_invoice_number'    => $raw['InvoiceNumber']    ?? null,
                'xero_branding_theme_id' => $raw['BrandingThemeID']  ?? null,

                'type'   => $raw['Type']   ?? null,
                'status' => $raw['Status'] ?? null,

                'xero_contact_id'      => $localContactId,
                'xero_contact_xero_id' => $xeroContactXeroId,

                'invoice_date'       => $this->parseXeroDate($raw['Date']            ?? null)?->toDateString(),
                'due_date'           => $this->parseXeroDate($raw['DueDate']         ?? null)?->toDateString(),
                'fully_paid_on_date' => $this->parseXeroDate($raw['FullyPaidOnDate'] ?? null)?->toDateString(),

                'reference'       => $raw['Reference']     ?? null,
                'url'             => $raw['Url']           ?? null,
                'sent_to_contact' => (bool) ($raw['SentToContact'] ?? false),

                'currency_code'   => $raw['CurrencyCode']   ?? null,
                'currency_rate'   => $raw['CurrencyRate']   ?? null,
                'sub_total'       => $raw['SubTotal']       ?? null,
                'total_tax'       => $raw['TotalTax']       ?? null,
                'total'           => $raw['Total']          ?? null,
                'total_discount'  => $raw['TotalDiscount']  ?? null,
                'amount_due'      => $raw['AmountDue']      ?? null,
                'amount_paid'     => $raw['AmountPaid']     ?? null,
                'amount_credited' => $raw['AmountCredited'] ?? null,

                'line_items'      => $this->serializeLineItems($raw['LineItems'] ?? []),
                'has_attachments' => (bool) ($raw['HasAttachments'] ?? false),

                'xero_updated_at' => $this->parseXeroDate($raw['UpdatedDateUTC'] ?? null),
                'last_synced_at'  => now(),
            ],
        );
    }

    private function serializeLineItems(array $items): ?array
    {
        if (empty($items)) {
            return null;
        }

        return array_map(fn (array $item) => [
            'line_item_id'    => $item['LineItemID']      ?? null,
            'description'     => $item['Description']     ?? null,
            'quantity'        => $item['Quantity']        ?? null,
            'unit_amount'     => $item['UnitAmount']      ?? null,
            'account_code'    => $item['AccountCode']     ?? null,
            'account_id'      => $item['AccountID']       ?? null,
            'item_code'       => $item['ItemCode']        ?? null,
            'tax_type'        => $item['TaxType']         ?? null,
            'tax_amount'      => $item['TaxAmount']       ?? null,
            'line_amount'     => $item['LineAmount']      ?? null,
            'discount_rate'   => $item['DiscountRate']    ?? null,
            'discount_amount' => $item['DiscountAmount']  ?? null,
        ], $items);
    }

    private function parseXeroDate(?string $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (preg_match('#/Date\((\d+)[+-]\d{4}\)/#', $value, $m)
            || preg_match('#/Date\((\d+)\)/#', $value, $m)) {
            return Carbon::createFromTimestampMs((int) $m[1], 'UTC');
        }

        return Carbon::parse($value);
    }
}
