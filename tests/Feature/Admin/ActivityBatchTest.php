<?php

use App\Jobs\Concerns\CarriesActivityBatch;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Support\Activity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

class BatchProbeJob implements ShouldQueue
{
    use CarriesActivityBatch;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(?string $activityBatch = null)
    {
        $this->activityBatch = $activityBatch;
    }

    public function handle(): void
    {
        $this->runInActivityBatch(function (): void {
            $company = Company::create(['name' => 'Probe Co', 'slug' => 'probe-co-'.Str::random(8)]);
            Activity::record('probe ran', subject: $company, event: 'probe', logName: 'test');
        });
    }
}

test('a job without a batch mints one shared batch for all its rows', function () {
    dispatch(new BatchProbeJob);

    $logs = ActivityLog::where('log_name', 'test')->orWhere('description', 'like', '%Probe Co%')->get();

    expect($logs->count())->toBeGreaterThanOrEqual(2)
        ->and($logs->pluck('batch_uuid')->filter()->unique()->count())->toBe(1)
        ->and(Activity::getBatchUuid())->toBeNull();
});

test('a dispatched batch chains from request into the worker', function () {
    $parent = (string) Str::uuid();

    Activity::withBatch($parent, function () {
        dispatch(new BatchProbeJob(Activity::getBatchUuid()));
    });

    $logs = ActivityLog::where('log_name', 'test')->orWhere('description', 'like', '%Probe Co%')->get();

    expect($logs)->not->toBeEmpty();

    foreach ($logs as $log) {
        expect($log->batch_uuid)->toBe($parent);
    }

    expect(Activity::getBatchUuid())->toBeNull();
});
