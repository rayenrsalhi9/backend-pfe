<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait CacheableTrait
{
    protected function getCacheTtl(string $type = 'default'): int
    {
        $ttls = [
            'categories' => 86400,     // 24 hours
            'roles' => 21600,          // 6 hours
            'claims' => 21600,         // 6 hours
            'users' => 21600,          // 6 hours
            'blogs' => 3600,          // 1 hour
            'articles' => 3600,        // 1 hour
            'forums' => 3600,          // 1 hour
            'surveys' => 3600,         // 1 hour
            'documents' => 1800,       // 30 minutes
            'notifications' => 300,    // 5 minutes
            'reminders' => 300,        // 5 minutes
            'chat' => 60,              // 1 minute
            'calendar' => 900,         // 15 minutes
            'default' => 3600,         // 1 hour
        ];

        return $ttls[$type] ?? $ttls['default'];
    }

    protected function getCacheKey(string $entity, ...$params): string
    {
        $filtered = array_filter($params, function ($v) { return $v !== null && $v !== ''; });
        $mapped = array_map(function ($v) { return is_bool($v) ? ($v ? '1' : '0') : (string)$v; }, $filtered);
        return strtolower($entity) . ':' . implode(':', $mapped);
    }

    protected function taggedStore(array $tags)
    {
        try {
            return Cache::tags($tags);
        } catch (\BadMethodCallException $e) {
            return Cache::store(config('cache.taggable_store', 'redis'))->tags($tags);
        }
    }

    protected function cacheRemember(string $key, string $tag, int $ttl, \Closure $callback)
    {
        return $this->taggedStore([$tag])->remember($key, $ttl, $callback);
    }

    protected function cacheForget(string $key, string $tag): void
    {
        $this->taggedStore([$tag])->forget($key);
    }

    protected function flushCacheTag(string $tag): void
    {
        $this->taggedStore([$tag])->flush();
    }
}
