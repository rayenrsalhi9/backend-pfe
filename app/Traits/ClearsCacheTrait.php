<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait ClearsCacheTrait
{
    protected static function bootClearsCacheTrait(): void
    {
        static::created(function ($model) {
            \DB::afterCommit(function () use ($model) {
                try {
                    $model->clearEntityCache();
                } catch (\Throwable $e) {
                    \Log::error('Cache clear failed on created: ' . $e->getMessage(), ['class' => get_class($model), 'id' => $model->id ?? null]);
                }
            });
        });

        static::updated(function ($model) {
            \DB::afterCommit(function () use ($model) {
                try {
                    $model->clearEntityCache();
                    $model->clearItemCache();
                } catch (\Throwable $e) {
                    \Log::error('Cache clear failed on updated: ' . $e->getMessage(), ['class' => get_class($model), 'id' => $model->id ?? null]);
                }
            });
        });

        static::deleted(function ($model) {
            \DB::afterCommit(function () use ($model) {
                try {
                    $model->clearEntityCache();
                    $model->clearItemCache();
                } catch (\Throwable $e) {
                    \Log::error('Cache clear failed on deleted: ' . $e->getMessage(), ['class' => get_class($model), 'id' => $model->id ?? null]);
                }
            });
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
