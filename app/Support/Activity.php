<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class Activity
{
    /**
     * Record an activity entry. Never throws — logging must not break the request.
     */
    public static function record(
        string $description,
        ?Model $subject = null,
        ?string $event = null,
        array $properties = [],
        string $logName = 'default',
        ?Model $causer = null,
    ): ?ActivityLog {
        try {
            if (! Schema::hasTable('activity_logs')) {
                return null;
            }

            $causer = $causer ?? Auth::user();
            $request = request();

            return ActivityLog::create([
                'log_name' => $logName,
                'event' => $event,
                'description' => $description,
                'subject_type' => $subject ? $subject->getMorphClass() : null,
                'subject_id' => $subject && $subject->exists ? $subject->getKey() : null,
                'causer_type' => $causer ? $causer->getMorphClass() : null,
                'causer_id' => $causer && $causer->exists ? $causer->getKey() : null,
                'properties' => array_merge(
                    $properties,
                    $causer && isset($causer->name) ? ['causer_name' => $causer->name, 'causer_email' => $causer->email ?? null] : []
                ) ?: null,
                'url' => $request ? substr($request->fullUrl() ?? '', 0, 500) : null,
                'method' => $request ? $request->method() : null,
                'ip' => $request ? $request->ip() : null,
                'user_agent' => $request ? substr((string) $request->userAgent(), 0, 1000) : null,
            ]);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function logModelEvent(string $event, Model $model, array $properties = []): ?ActivityLog
    {
        $label = method_exists($model, 'activityLabel')
            ? $model->activityLabel()
            : class_basename($model).' #'.$model->getKey();

        $descriptions = [
            'created' => "Created {$label}",
            'updated' => "Updated {$label}",
            'deleted' => "Deleted {$label}",
            'restored' => "Restored {$label}",
        ];

        return static::record(
            description: $descriptions[$event] ?? ucfirst($event).' '.$label,
            subject: $model,
            event: $event,
            properties: $properties,
            logName: 'model',
        );
    }
}
