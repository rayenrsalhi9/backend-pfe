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
        try {
            $store = $this->taggedStore([$tag]);
            $value = $store->get($key);
            if ($value !== null) {
                return $value;
            }
        } catch (\Throwable $e) {
            \Log::error('Cache read failed: ' . $e->getMessage());
        }

        $value = $callback();

        try {
            $this->taggedStore([$tag])->put($key, $value, $ttl);
        } catch (\Throwable $e) {
            \Log::error('Cache write failed: ' . $e->getMessage());
        }

        return $value;
    }

    protected function cacheForget(string $key, string $tag): void
    {
        try {
            $this->taggedStore([$tag])->forget($key);
        } catch (\Throwable $e) {
            \Log::error('Cache forget failed: ' . $e->getMessage());
        }
    }

    protected function flushCacheTag(string $tag): void
    {
        try {
            $this->taggedStore([$tag])->flush();
        } catch (\Throwable $e) {
            \Log::error('Cache flush failed: ' . $e->getMessage());
        }
    }

    protected function normalizeRequestParams(array $params): string
    {
        $normalized = $this->recursiveSort($params);
        return md5(json_encode($normalized));
    }

    private function recursiveSort(array &$array): array
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->recursiveSort($value);
            }
        }
        return $array;
    }
}
