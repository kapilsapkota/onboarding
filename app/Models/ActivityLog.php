<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property array|null $properties
 */
class ActivityLog extends Model
{
    protected $fillable = [
        'log_name',
        'event',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'url',
        'method',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function getCauserNameAttribute(): string
    {
        $causer = $this->causer;

        if ($causer && isset($causer->name)) {
            return (string) $causer->name;
        }

        return $this->properties['causer_name'] ?? 'System';
    }
}
