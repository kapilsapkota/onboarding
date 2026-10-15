<?php

namespace App\Jobs\Concerns;

use App\Support\Activity;

/**
 * Chains the activity-log batch from dispatch → queue worker.
 *
 * The dispatching request/command passes Activity::getBatchUuid() as the
 * trailing $activityBatch constructor arg. On handle(), every model / mail /
 * sms / notification row written by the job shares that batch, so the log
 * viewer shows one user action with all its side-effects.
 *
 * Prod-safety: the arg is always optional (existing dispatch calls keep
 * working), a fresh batch is minted when none was passed, and the previous
 * batch is restored afterwards so nested dispatches never leak context.
 */
trait CarriesActivityBatch
{
    public ?string $activityBatch = null;

    /**
     * Run $work with this job's batch active. Returns whatever $work returns;
     * exceptions propagate untouched so queue retries behave exactly as before.
     */
    protected function runInActivityBatch(callable $work): mixed
    {
        return Activity::withBatch($this->activityBatch, $work);
    }
}
