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

    /**
     * Initials for the avatar fallback (e.g. "AR" for "Ada Ray").
     */
    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->causer_name)) ?: [];

        $initials = implode('', array_map(
            fn (string $part): string => strtoupper(mb_substr($part, 0, 1)),
            array_slice($parts, 0, 2)
        ));

        return $initials !== '' ? $initials : '?';
    }

    /**
     * Avatar image URL when the causer has one, null for the initial fallback.
     */
    public function avatarUrl(): ?string
    {
        $causer = $this->causer;

        if ($causer && method_exists($causer, 'getAvatarUrlAttribute')) {
            return $causer->avatar_url;
        }

        return null;
    }

    /**
     * Deterministic avatar background so each user is recognisable at a glance.
     */
    public function avatarColor(): string
    {
        $palette = [
            'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
            'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
            'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
            'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
            'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
        ];

        return $palette[abs(crc32($this->causer_name)) % count($palette)];
    }

    /**
     * Icon key + tailwind theme for the event badge.
     *
     * @return array{icon: string, dot: string, badge: string}
     */
    public function eventTheme(): array
    {
        return match (strtolower((string) $this->event)) {
            'created' => [
                'icon' => 'plus',
                'dot' => 'bg-emerald-500',
                'badge' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
            ],
            'updated' => [
                'icon' => 'pencil',
                'dot' => 'bg-amber-500',
                'badge' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
            ],
            'deleted' => [
                'icon' => 'trash',
                'dot' => 'bg-rose-500',
                'badge' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
            ],
            'restored' => [
                'icon' => 'restore',
                'dot' => 'bg-teal-500',
                'badge' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',
            ],
            'login' => [
                'icon' => 'login',
                'dot' => 'bg-sky-500',
                'badge' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
            ],
            'logout' => [
                'icon' => 'logout',
                'dot' => 'bg-slate-400',
                'badge' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
            ],
            'view' => [
                'icon' => 'eye',
                'dot' => 'bg-slate-400',
                'badge' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
            ],
            'sent' => [
                'icon' => 'mail',
                'dot' => 'bg-violet-500',
                'badge' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
            ],
            'failed' => [
                'icon' => 'alert',
                'dot' => 'bg-red-500',
                'badge' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
            ],
            default => [
                'icon' => 'activity',
                'dot' => 'bg-indigo-500',
                'badge' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
            ],
        };
    }

    /**
     * Normalised old → new change list for inline diffs.
     *
     * @return array<int, array{field: string, old: mixed, new: mixed}>
     */
    public function changesList(int $limit = 3): array
    {
        $changes = $this->properties['changes'] ?? [];

        if (! is_array($changes)) {
            return [];
        }

        $rows = [];

        foreach ($changes as $field => $change) {
            $rows[] = [
                'field' => (string) $field,
                'old' => is_array($change) ? ($change['old'] ?? null) : null,
                'new' => is_array($change) ? ($change['new'] ?? null) : $change,
            ];

            if (count($rows) >= $limit) {
                break;
            }
        }

        return $rows;
    }

    /**
     * Remaining change count beyond the inline preview limit.
     */
    public function remainingChangesCount(int $limit = 3): int
    {
        $changes = $this->properties['changes'] ?? [];

        return is_array($changes) ? max(0, count($changes) - $limit) : 0;
    }

    /**
     * Short scalar preview for diff values (arrays/objects collapse to JSON).
     */
    public function previewValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            $text = (string) $value;

            return mb_strlen($text) > 60 ? mb_substr($text, 0, 60).'…' : $text;
        }

        $json = json_encode($value);

        return $json !== false && mb_strlen($json) > 60 ? mb_substr($json, 0, 60).'…' : (string) $json;
    }
}
