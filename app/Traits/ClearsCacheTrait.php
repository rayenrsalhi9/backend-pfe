<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait ClearsCacheTrait
{
    protected static function bootClearsCacheTrait(): void
    {
        static::created(function ($model) {
            $model->clearEntityCache();
        });

        static::updated(function ($model) {
            $model->clearEntityCache();
            $model->clearItemCache();
        });

        static::deleted(function ($model) {
            $model->clearEntityCache();
            $model->clearItemCache();
        });
    }

    protected function getEntityName(): string
    {
        $name = class_basename($this);
        if ($name === 'RoleClaims') return 'claims';
        if ($name === 'UserNotifications') return 'notifications';
        return strtolower($name);
    }

    protected function taggedStore(array $tags)
    {
        try {
            return Cache::tags($tags);
        } catch (\BadMethodCallException $e) {
            return Cache::store(config('cache.taggable_store', 'redis'))->tags($tags);
        }
    }

    protected function clearEntityCache(): void
    {
        $entity = $this->getEntityName();
        $this->taggedStore([$entity])->flush();
    }

    protected function clearItemCache(): void
    {
        $entity = $this->getEntityName();
        $this->taggedStore([$entity])->forget("{$entity}:{$this->id}");
    }
}
