<?php

namespace App\Console\Commands;

use App\Jobs\SyncXeroTenantContacts;
use App\Jobs\SyncTenantInvoiceJob;
use App\Jobs\SyncXeroRepeatingInvoices;
use App\Models\XeroTenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncXeroCommand extends Command
{
    protected $signature = 'xero:sync
        {--tenant= : Sync specific tenant (id or tenant_id)}
        {--mode=all : contacts|invoices|repeating|all|full}
        {--now : Run synchronously instead of queue}';

    protected $description = 'Robust Xero sync system (production safe)';

    public function handle(): int
    {
        $tenants = $this->resolveTenants();

        if ($tenants->isEmpty()) {
            $this->error('No active Xero tenants found.');
            return self::FAILURE;
        }

        $mode   = $this->option('mode');
        $runNow = (bool) $this->option('now');

        if (! in_array($mode, ['contacts','invoices','repeating','all','full'])) {
            $this->error("Invalid mode: {$mode}");
            return self::FAILURE;
        }

        $this->info("Starting Xero sync in [{$mode}] mode");

        $totalQueued = 0;

        foreach ($tenants as $index => $tenant) {

            try {

                if (! $tenant->connection) {
                    $this->warn("Tenant {$tenant->tenant_name} skipped (no connection)");
                    continue;
                }

                $lockKey = "xero-sync:{$tenant->id}:{$mode}";

                if (Cache::has($lockKey)) {
                    $this->warn("Skipping {$tenant->tenant_name} (already syncing)");
                    continue;
                }

                Cache::put($lockKey, true, now()->addMinutes(5));

                $this->line("▶ Tenant: {$tenant->tenant_name}");

                $delay = $index * 5;

                $count = $this->runMode($tenant, $mode, $runNow, $delay);

                $totalQueued += $count;

                Log::info('Xero sync dispatched', [
                    'tenant_id' => $tenant->id,
                    'mode' => $mode,
                    'jobs' => $count,
                ]);

            } catch (\Throwable $e) {

                Log::error('Xero sync tenant failed', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);

                $this->error("Tenant {$tenant->tenant_name} failed: {$e->getMessage()}");

            } finally {
                // unlock early so next run is not blocked forever
                Cache::forget("xero-sync:{$tenant->id}:{$mode}");
            }
        }

        $verb = $runNow ? 'Executed' : 'Queued';

        $this->info("{$verb} {$totalQueued} job(s) across {$tenants->count()} tenant(s).");

        return self::SUCCESS;
    }

    // -------------------------------------------------------------

    private function runMode($tenant, string $mode, bool $runNow, int $delay): int
    {
        return match ($mode) {

            'contacts' => $this->dispatchJob(
                new SyncXeroTenantContacts($tenant->connection->id, $tenant->id),
                $runNow,
                'contacts',
                $delay
            ),

            'invoices' => $this->dispatchJob(
                new SyncTenantInvoiceJob(
                    connectionId: $tenant->connection->id,
                    tenantId: $tenant->id,
                    modifiedAfter: $tenant->last_invoice_synced_at,
                    fullResync: false
                ),
                $runNow,
                'invoices',
                $delay
            ),

            'repeating' => $this->dispatchJob(
                new SyncXeroRepeatingInvoices($tenant->connection->id, $tenant->id),
                $runNow,
                'repeating invoices',
                $delay
            ),

            'all' => $this->runAll($tenant, false, $runNow, $delay),

            'full' => $this->runAll($tenant, true, $runNow, $delay),

        };
    }

    // -------------------------------------------------------------

    private function runAll($tenant, bool $full, bool $runNow, int $delay): int
    {
        $count = 0;

        $count += $this->dispatchJob(
            new SyncXeroTenantContacts($tenant->connection->id, $tenant->id),
            $runNow,
            'contacts',
            $delay
        );

        $count += $this->dispatchJob(
            new SyncTenantInvoiceJob(
                connectionId: $tenant->connection->id,
                tenantId: $tenant->id,
                modifiedAfter: $full ? null : $tenant->last_invoice_synced_at,
                fullResync: $full
            ),
            $runNow,
            'invoices',
            $delay
        );

        $count += $this->dispatchJob(
            new SyncXeroRepeatingInvoices($tenant->connection->id, $tenant->id),
            $runNow,
            'repeating invoices',
            $delay
        );

        return $count;
    }

    // -------------------------------------------------------------

    private function dispatchJob(object $job, bool $runNow, string $label, int $delay = 0): int
    {
        if ($runNow) {
            $this->line(" → Running {$label}...");
            app()->call([$job, 'handle']);
            $this->line(" ✓ {$label} done.");
            return 1;
        }

        dispatch($job)
            ->delay(now()->addSeconds($delay));

        $this->line(" → Queued {$label} (delay {$delay}s)");

        return 1;
    }

    // -------------------------------------------------------------

    private function resolveTenants()
    {
        $tenantOption = $this->option('tenant');

        return XeroTenant::with('connection')
            ->where('is_active', true)
            ->when($tenantOption, function ($q) use ($tenantOption) {
                $q->where('id', $tenantOption)
                    ->orWhere('tenant_id', $tenantOption);
            })
            ->get();
    }
}
