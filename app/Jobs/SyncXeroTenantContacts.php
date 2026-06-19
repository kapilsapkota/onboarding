<?php

namespace App\Jobs;

use App\Models\XeroConnection;
use App\Models\XeroTenant;
use App\Services\XeroContactSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncXeroTenantContacts implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $connectionId,
        public int $tenantId
    ) {}

    public function handle(
        XeroContactSyncService $syncService
    ): void
    {
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
