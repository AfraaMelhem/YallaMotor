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
        // Use the same pattern as DealerService - pass data directly to repository
        $cacheKey = $this->generateCacheKey($data, $perPage, $includeFacets);

        // Generate cache tags
        $tags = ['cars_list'];
        if (!empty($data['filters']['countryCode'])) {
            $tags[] = "country:{$data['filters']['countryCode']}";
        }

        // Use cache with tagging
        return $this->cacheService->remember($cacheKey, $tags, 300, function () use ($data, $perPage, $includeFacets, $cacheKey) {
            // Use getPaginatedList from BaseRepository (same as DealerService)
            $cars = $this->listingRepository->getPaginatedList($data, $perPage);

            $result = [
                'cars' => $cars,
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

    public function showWithRelations(int $id)
    {
        $cacheKey = "car_full:{$id}";
        $tags = ["listing:{$id}"];

        return $this->cacheService->remember($cacheKey, $tags, 600, function () use ($id) {
            return $this->listingRepository->show($id)->load(['dealer', 'events' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(5);
            }]);
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

    private function generateCacheKey(array $data, int $perPage, bool $includeFacets): string
    {
        $filters = $data['filters'] ?? [];
        $sort = $data['sort'] ?? [];

        ksort($filters);
        ksort($sort);

        $components = [
            'filters' => $filters,
            'sort' => $sort,
            'search' => $data['search'] ?? null,
            'per_page' => $perPage,
            'facets' => $includeFacets
        ];

        return 'cars:' . md5(serialize($components));
    }

    public function getPopularMakes(?string $countryCode = null): array
    {
        $cacheKey = "popular_makes" . ($countryCode ? ":{$countryCode}" : "");
        $tags = ['popular_makes'];
        if ($countryCode) {
            $tags[] = "country:{$countryCode}";
        }

        return $this->cacheService->remember($cacheKey, $tags, 1800, function () use ($countryCode) {
            $query = $this->listingRepository->getActiveListing();

            if ($countryCode) {
                $query->where('country_code', strtoupper($countryCode));
            }

            $makes = $query->selectRaw('make, COUNT(*) as count')
                ->whereNotNull('make')
                ->where('make', '!=', '')
                ->groupBy('make')
                ->orderBy('count', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($item) {
                    return [
                        'make' => $item->make,
                        'count' => $item->count
                    ];
                })
                ->toArray();

            return [
                'makes' => $makes,
                'total_makes' => count($makes),
                'country_code' => $countryCode ? strtoupper($countryCode) : null
            ];
        });
    }

}
