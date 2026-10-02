<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

class PruneActivityLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity:prune {--days=180 : Delete activity log entries older than this many days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete activity log entries older than the given retention window';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));

        $deleted = ActivityLog::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("Pruned {$deleted} activity log entries older than {$days} days.");

        return self::SUCCESS;
    }
}
