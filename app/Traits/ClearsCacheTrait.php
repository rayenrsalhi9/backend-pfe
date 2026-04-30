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
        return strtolower(class_basename($this));
    }

    protected function clearEntityCache(): void
    {
        $entity = $this->getEntityName();
        Cache::tags([$entity])->flush();
    }

    protected function clearItemCache(): void
    {
        $entity = $this->getEntityName();
        Cache::tags([$entity])->forget("{$entity}:{$this->id}");
    }
}
