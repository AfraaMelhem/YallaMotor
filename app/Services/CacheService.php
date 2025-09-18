<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class CacheService
{
    private const TAG_PREFIX = 'tag:';
    private const KEY_PREFIX = 'key:';

    public function remember(string $key, array $tags, int $ttl, callable $callback)
    {
        // Check if key exists in cache
        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached;
        }

        // Generate the value
        $value = $callback();

        // Store the value
        Cache::put($key, $value, $ttl);

        // Store tag relationships
        $this->tagKey($key, $tags);

        return $value;
    }

    public function put(string $key, $value, int $ttl, array $tags = []): void
    {
        Cache::put($key, $value, $ttl);

        if (!empty($tags)) {
            $this->tagKey($key, $tags);
        }
    }

    public function forget(string $key): void
    {
        Cache::forget($key);
        $this->removeKeyFromAllTags($key);
    }

    public function flush(array $tags = []): array
    {
        $purgedKeys = [];

        if (empty($tags)) {
            // Flush all cache
            Cache::flush();
            $this->clearAllTagMappings();
            return ['all_cache_cleared' => true];
        }

        // Flush by tags
        foreach ($tags as $tag) {
            $keys = $this->getKeysForTag($tag);
            foreach ($keys as $key) {
                Cache::forget($key);
                $purgedKeys[] = $key;
            }
            $this->clearTag($tag);
        }

        return $purgedKeys;
    }

    public function flushByKeys(array $keys): array
    {
        $purgedKeys = [];

        foreach ($keys as $key) {
            if (Cache::has($key)) {
                Cache::forget($key);
                $this->removeKeyFromAllTags($key);
                $purgedKeys[] = $key;
            }
        }

        return $purgedKeys;
    }

    private function tagKey(string $key, array $tags): void
    {
        foreach ($tags as $tag) {
            $tagKey = self::TAG_PREFIX . $tag;

            // Get existing keys for this tag
            $existingKeys = Cache::get($tagKey, []);

            // Add this key if not already present
            if (!in_array($key, $existingKeys)) {
                $existingKeys[] = $key;
                Cache::forever($tagKey, $existingKeys);
            }

            // Also store reverse mapping (key -> tags)
            $keyTagsKey = self::KEY_PREFIX . $key;
            $existingTags = Cache::get($keyTagsKey, []);
            if (!in_array($tag, $existingTags)) {
                $existingTags[] = $tag;
                Cache::forever($keyTagsKey, $existingTags);
            }
        }
    }

    private function getKeysForTag(string $tag): array
    {
        $tagKey = self::TAG_PREFIX . $tag;
        return Cache::get($tagKey, []);
    }

    private function clearTag(string $tag): void
    {
        $tagKey = self::TAG_PREFIX . $tag;
        Cache::forget($tagKey);
    }

    private function removeKeyFromAllTags(string $key): void
    {
        $keyTagsKey = self::KEY_PREFIX . $key;
        $tags = Cache::get($keyTagsKey, []);

        foreach ($tags as $tag) {
            $tagKey = self::TAG_PREFIX . $tag;
            $keys = Cache::get($tagKey, []);
            $keys = array_filter($keys, fn($k) => $k !== $key);

            if (empty($keys)) {
                Cache::forget($tagKey);
            } else {
                Cache::forever($tagKey, array_values($keys));
            }
        }

        Cache::forget($keyTagsKey);
    }

    private function clearAllTagMappings(): void
    {
        Log::info('Cache service: All tag mappings cleared via cache flush');
    }

    public function generateListingTags(int $listingId, ?string $countryCode = null, ?int $dealerId = null): array
    {
        $tags = ["listing:{$listingId}"];

        if ($countryCode) {
            $tags[] = "country:{$countryCode}";
        }

        if ($dealerId) {
            $tags[] = "dealer:{$dealerId}";
        }

        return $tags;
    }

    public function generateDealerTags(int $dealerId, ?string $countryCode = null): array
    {
        $tags = ["dealer:{$dealerId}"];

        if ($countryCode) {
            $tags[] = "country:{$countryCode}";
        }

        return $tags;
    }

    public function invalidateListingCaches(int $listingId, ?string $countryCode = null, ?int $dealerId = null): array
    {
        $tags = $this->generateListingTags($listingId, $countryCode, $dealerId);

        $tags[] = 'cars_list';
        $tags[] = 'facets';
        $tags[] = 'statistics';

        return $this->flush($tags);
    }


}
