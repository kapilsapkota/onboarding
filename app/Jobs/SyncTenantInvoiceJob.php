<?php

namespace App\Jobs;

use App\Models\XeroConnection;
use App\Models\XeroTenant;
use App\Services\XeroInvoiceSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncTenantInvoiceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param int         $connectionId
     * @param int         $tenantId
     * @param string|null $modifiedAfter  ISO-8601 date; when set only invoices updated
     *                                    after this timestamp are fetched. Defaults to
     *                                    the tenant's last_invoice_sync_at.
     * @param bool        $fullResync     Ignore modifiedAfter and pull all invoices
     *                                    (expensive – use sparingly).
     */
    public function __construct(
        public int     $connectionId,
        public int     $tenantId,
        public ?string $modifiedAfter = null,
        public bool    $fullResync    = false,
    ) {}

    public function handle(XeroInvoiceSyncService $syncService): void
    {
        $connection = XeroConnection::findOrFail($this->connectionId);
        $tenant     = XeroTenant::findOrFail($this->tenantId);

        $modifiedAfter = match (true) {
            $this->fullResync             => null,
            $this->modifiedAfter !== null => $this->modifiedAfter,
            $tenant->last_invoice_synced_at !== null => $tenant->last_invoice_synced_at->toIso8601String(),
            default                       => null,
        };

        try {
            $syncService->sync($connection, $tenant, $modifiedAfter);

            $tenant->update([
                'last_invoice_synced_at' => now(),
            ]);

        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
