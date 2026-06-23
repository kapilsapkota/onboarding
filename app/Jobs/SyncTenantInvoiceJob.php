<?php

namespace App\Jobs;

use App\Exceptions\XeroAuthException;
use App\Models\XeroConnection;
use App\Models\XeroTenant;
use App\Services\XeroInvoiceSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncTenantInvoiceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Retry transient failures (network blips, Xero 5xx) up to 3 times
     * with exponential back-off: 1 min, 2 min, 4 min.
     * XeroAuthException bypasses this entirely via $this->fail().
     */
    public int $tries = 3;

    public function backoff(): array
    {
        return [60, 120, 240];
    }

    /**
     * @param int         $connectionId
     * @param int         $tenantId
     * @param string|null $invoiceId     When provided, sync only this invoice (webhook path).
     *                                   When null, sync all invoices for the tenant (scheduled path).
     * @param string|null $modifiedAfter ISO-8601 date; when set only invoices updated after
     *                                   this timestamp are fetched. Defaults to the tenant's
     *                                   last_invoice_synced_at. Ignored when $invoiceId is set.
     * @param bool        $fullResync    Ignore modifiedAfter and pull all invoices
     *                                   (expensive – use sparingly). Ignored when $invoiceId is set.
     */
    public function __construct(
        public int     $connectionId,
        public int     $tenantId,
        public ?string $invoiceId     = null,
        public ?string $modifiedAfter = null,
        public bool    $fullResync    = false,
    ) {}

    public function handle(XeroInvoiceSyncService $syncService): void
    {
        $connection = XeroConnection::findOrFail($this->connectionId);
        $tenant     = XeroTenant::findOrFail($this->tenantId);

        try {
            if ($this->invoiceId !== null) {
                $syncService->syncOne($connection, $tenant, $this->invoiceId);
            } else {
                $modifiedAfter = match (true) {
                    $this->fullResync                        => null,
                    $this->modifiedAfter !== null            => $this->modifiedAfter,
                    $tenant->last_invoice_synced_at !== null => $tenant->last_invoice_synced_at->toIso8601String(),
                    default                                  => null,
                };

                $syncService->sync($connection, $tenant, $modifiedAfter);

                $tenant->update(['last_invoice_synced_at' => now()]);
            }

        } catch (XeroAuthException $e) {
            Log::error('SyncTenantInvoiceJob: aborting — Xero re-authorization required', [
                'connection_id' => $this->connectionId,
                'tenant_id'     => $this->tenantId,
                'invoice_id'    => $this->invoiceId,
                'reason'        => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }
}
