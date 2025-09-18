<?php

namespace App\Services;

use App\Repositories\Interfaces\ListingRepositoryInterface;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CarService
{
    public function __construct(
        private ListingRepositoryInterface $listingRepository,
        private CacheService $cacheService
    ) {}

    public function getFilteredCars(array $data = [], int $perPage = 20, bool $includeFacets = false)
    {
        $filters = $data['filters'] ?? [];
        $sort = $data['sort'] ?? ['listed_at' => 'desc'];

        // Generate cache key with facets flag
        $cacheKey = $this->generateCacheKey($filters, $sort, $perPage, $includeFacets);

        // Generate cache tags
        $tags = ['cars_list'];
        if (!empty($filters['country_code'])) {
            $tags[] = "country:{$filters['country_code']}";
        }

        // Use cache with tagging
        return $this->cacheService->remember($cacheKey, $tags, 300, function () use ($data, $perPage, $includeFacets, $cacheKey) {
            $query = $this->listingRepository->getFastBrowseData($data['filters'] ?? []);

            // Apply sorting
            foreach ($data['sort'] ?? ['listed_at' => 'desc'] as $field => $direction) {
                $query->orderBy($field, $direction);
            }

            $result = [
                'cars' => $query->with(['dealer'])->paginate($perPage),
                'query_time_ms' => round((microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))) * 1000, 2),
                'cache_key' => $cacheKey
            ];

            // Add facets if requested
            if ($includeFacets) {
                $result['facets'] = $this->generateFacets($data['filters'] ?? []);
            }

            return $result;
        });
    }

    public function show(int $id)
    {
        $cacheKey = "car:{$id}";
        $tags = ["listing:{$id}"];

        return $this->cacheService->remember($cacheKey, $tags, 600, function () use ($id) {
            return $this->listingRepository->show($id);
        });
    }

    private function generateFacets(array $appliedFilters): array
    {
        // Cache facets separately for better performance
        $facetCacheKey = 'car_facets:' . md5(serialize($appliedFilters));
        $tags = ['facets'];

        if (!empty($appliedFilters['country_code'])) {
            $tags[] = "country:{$appliedFilters['country_code']}";
        }

        return $this->cacheService->remember($facetCacheKey, $tags, 900, function () use ($appliedFilters) {
            $baseQuery = $this->listingRepository->getActiveListing();

            // Apply current filters except the ones we're faceting on
            $filtersForFacets = collect($appliedFilters)->except(['make', 'year'])->toArray();
            if (!empty($filtersForFacets)) {
                $baseQuery = $this->listingRepository->getFastBrowseData($filtersForFacets);
            }

            return [
                'makes' => $baseQuery->clone()
                    ->selectRaw('make, COUNT(*) as count')
                    ->whereNotNull('make')
                    ->groupBy('make')
                    ->orderBy('count', 'desc')
                    ->limit(20)
                    ->pluck('count', 'make')
                    ->toArray(),

                'years' => $baseQuery->clone()
                    ->selectRaw('year, COUNT(*) as count')
                    ->whereNotNull('year')
                    ->groupBy('year')
                    ->orderBy('year', 'desc')
                    ->pluck('count', 'year')
                    ->toArray(),
            ];
        });
    }

    private function generateCacheKey(array $filters, array $sort, int $perPage, bool $includeFacets): string
    {
        ksort($filters);
        ksort($sort);

        $components = [
            'filters' => $filters,
            'sort' => $sort,
            'per_page' => $perPage,
            'facets' => $includeFacets
        ];

        return 'cars:' . md5(serialize($components));
    }

    private function getCachedWithStampedeProtection(string $key, int $ttl, callable $callback)
    {
        // Try to get from cache first
        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached;
        }

        // Simple cache generation without stampede protection for now
        try {
            $result = $callback();
            Cache::put($key, $result, $ttl);
            return $result;
        } catch (\Exception $e) {
            Log::error('Cache generation failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            // Return the result without caching
            return $callback();
        }
    }

    public function getCarStatistics(array $filters = []): array
    {
        $cacheKey = 'car_statistics:' . md5(serialize($filters));

        return Cache::remember($cacheKey, 1800, function () use ($filters) {
            $query = $this->listingRepository->getFastBrowseData($filters);

            return [
                'total_cars' => $query->count(),
                'average_price' => round($query->avg('price_cents') / 100, 2),
                'price_range' => [
                    'min' => round($query->min('price_cents') / 100, 2),
                    'max' => round($query->max('price_cents') / 100, 2),
                ],
                'year_range' => [
                    'min' => $query->min('year'),
                    'max' => $query->max('year'),
                ],
                'makes_count' => $query->distinct('make')->count('make'),
                'countries_count' => $query->distinct('country_code')->count('country_code'),
            ];
        });
    }
}