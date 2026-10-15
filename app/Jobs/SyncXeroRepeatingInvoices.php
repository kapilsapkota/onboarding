<?php

namespace App\Jobs;

use App\Exceptions\XeroAuthException;
use App\Jobs\Concerns\CarriesActivityBatch;
use App\Models\XeroConnection;
use App\Models\XeroTenant;
use App\Services\XeroRepeatingSyncService;
use App\Services\XeroService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncXeroRepeatingInvoices implements ShouldQueue
{
    use CarriesActivityBatch;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $connectionId,
        public int $tenantId,
        ?string $activityBatch = null,
    ) {
        $this->activityBatch = $activityBatch;
    }

    public function handle(XeroRepeatingSyncService $sync, XeroService $xero): void
    {
        $this->runInActivityBatch(fn () => $this->handleBatched($sync, $xero));
    }

    private function handleBatched(XeroRepeatingSyncService $sync, XeroService $xero): void
    {
        $connection = XeroConnection::findOrFail($this->connectionId);
        $tenant = XeroTenant::findOrFail($this->tenantId);

        try {
            $sync->sync($connection, $tenant);
        } catch (XeroAuthException $e) {
            Log::error('SyncXeroRepeatingInvoices: aborting — Xero re-authorization required', [
                'connection_id' => $this->connectionId,
                'tenant_id' => $this->tenantId,
                'reason' => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }
}
