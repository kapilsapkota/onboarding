<?php

namespace App\Console\Commands;

use App\Jobs\SyncXeroTenantContacts;
use App\Jobs\SyncTenantInvoiceJob;
use App\Jobs\SyncXeroRepeatingInvoices;
use App\Models\XeroTenant;
use Illuminate\Console\Command;

class SyncXeroCommand extends Command
{
    protected $signature = 'xero:sync
                            {--tenant=     : Sync a specific tenant by ID or tenant_id UUID (default: all)}
                            {--invoices    : Sync invoices}
                            {--contacts    : Sync contacts}
                            {--repeating   : Sync repeating invoice templates}
                            {--all         : Sync everything (invoices + contacts + repeating)}
                            {--full        : Full resync — ignore last_synced_at watermark}
                            {--now         : Run synchronously instead of dispatching to the queue}';

    protected $description = 'Sync invoices, contacts, and/or repeating invoice templates from Xero';

    public function handle(): int
    {
        $tenants = $this->resolveTenants();

        if ($tenants->isEmpty()) {
            $this->error('No active Xero tenants found.');
            return self::FAILURE;
        }

        $syncInvoices  = $this->option('invoices')  || $this->option('all');
        $syncContacts  = $this->option('contacts')  || $this->option('all');
        $syncRepeating = $this->option('repeating') || $this->option('all');

        if (! $syncInvoices && ! $syncContacts && ! $syncRepeating) {
            $this->error('Specify at least one of --invoices, --contacts, --repeating, or --all.');
            return self::FAILURE;
        }

        $fullResync = (bool) $this->option('full');
        $runNow     = (bool) $this->option('now');
        $queued     = 0;

        foreach ($tenants as $tenant) {
            if (! $tenant->connection) {
                $this->warn("  Skipping tenant [{$tenant->id}] {$tenant->name} — no active connection.");
                continue;
            }

            $this->line("  <fg=cyan>Tenant</> [{$tenant->id}] <fg=white>{$tenant->name}</>");

            if ($syncContacts) {
                $this->dispatchOrRun(
                    runNow: $runNow,
                    job:    new SyncXeroTenantContacts($tenant->connection->id, $tenant->id),
                    label:  'contacts',
                );
                $queued++;
            }

            if ($syncInvoices) {
                $this->dispatchOrRun(
                    runNow: $runNow,
                    job:    new SyncTenantInvoiceJob(
                        connectionId:  $tenant->connection->id,
                        tenantId:      $tenant->id,
                        modifiedAfter: null,
                        fullResync:    $fullResync,
                    ),
                    label:  'invoices',
                );
                $queued++;
            }

            if ($syncRepeating) {
                $this->dispatchOrRun(
                    runNow: $runNow,
                    job:    new SyncXeroRepeatingInvoices($tenant->connection->id, $tenant->id),
                    label:  'repeating invoices',
                );
                $queued++;
            }
        }

        $verb = $runNow ? 'Ran' : 'Queued';
        $this->info("{$verb} {$queued} sync job(s) across {$tenants->count()} tenant(s).");

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------

    private function resolveTenants()
    {
        $tenantOption = $this->option('tenant');

        $query = XeroTenant::with('connection')->where('is_active', true);

        if ($tenantOption) {
            $query->where(function ($q) use ($tenantOption) {
                $q->where('id', $tenantOption)
                    ->orWhere('tenant_id', $tenantOption);
            });
        }

        return $query->get();
    }

    private function dispatchOrRun(bool $runNow, object $job, string $label): void
    {
        if ($runNow) {
            $this->line("    → Running {$label} synchronously...");
            app()->call([$job, 'handle']);
            $this->line("    <fg=green>✓</> {$label} done.");
        } else {
            dispatch($job)->onQueue('xero-sync');
            $this->line("    → Queued {$label}.");
        }
    }
}
