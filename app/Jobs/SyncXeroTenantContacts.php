<?php

namespace App\Jobs;

use App\Jobs\Concerns\CarriesActivityBatch;
use App\Models\XeroConnection;
use App\Models\XeroTenant;
use App\Services\XeroContactSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncXeroTenantContacts implements ShouldQueue
{
    use CarriesActivityBatch;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $connectionId,
        public int $tenantId,
        public ?string $contactId = null,
        ?string $activityBatch = null,
    ) {
        $this->activityBatch = $activityBatch;
    }

    public function handle(
        XeroContactSyncService $syncService
    ): void {
        $this->runInActivityBatch(fn () => $this->handleBatched($syncService));
    }

    private function handleBatched(
        XeroContactSyncService $syncService
    ): void {
        Log::info('SyncXeroTenantContacts: job started', [
            'connection_id' => $this->connectionId,
            'tenant_id' => $this->tenantId,
            'contact_id' => $this->contactId,
        ]);

        $connection = XeroConnection::findOrFail(
            $this->connectionId
        );

        $tenant = XeroTenant::findOrFail(
            $this->tenantId
        );

        try {
            $syncService->sync(
                $connection,
                $tenant
            );

            $tenant->update([
                'last_contact_sync_at' => now(),
            ]);

        } catch (\Throwable $e) {

            $tenant->update([
                'sync_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
