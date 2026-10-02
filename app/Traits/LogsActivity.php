<?php

namespace App\Traits;

use App\Support\Activity;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            Activity::logModelEvent('created', $model, [
                'attributes' => $model->getAttributesForActivity(),
            ]);
        });

        static::updated(function ($model) {
            $changes = collect($model->getChanges())
                ->except(['updated_at'])
                ->mapWithKeys(fn ($value, $key) => [$key => [
                    'old' => $model->getOriginal($key),
                    'new' => $value,
                ]])
                ->toArray();

            if (empty($changes)) {
                return;
            }

            Activity::logModelEvent('updated', $model, ['changes' => $changes]);
        });

        static::deleted(function ($model) {
            Activity::logModelEvent('deleted', $model, [
                'attributes' => $model->getAttributesForActivity(),
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function getAttributesForActivity(): array
    {
        $hidden = array_merge($this->getHidden(), [
            'password', 'remember_token', 'secret_key', 'webhook_secret',
            'access_token', 'refresh_token',
        ]);

        return collect($this->getAttributes())
            ->except($hidden)
            ->toArray();
    }

    public function activityLabel(): string
    {
        foreach (['display_name', 'name', 'title', 'company_name', 'reference', 'email'] as $key) {
            if (! empty($this->getAttribute($key))) {
                return class_basename($this).' "'.$this->getAttribute($key).'" (#'.$this->getKey().')';
            }
        }

        return class_basename($this).' #'.$this->getKey();
    }
}
