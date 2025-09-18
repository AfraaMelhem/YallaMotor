<?php

namespace App\Services;

use App\Repositories\Interfaces\ListingRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ListingService
{
    public function __construct(
        private ListingRepositoryInterface $listingRepository
    ) {}

    public function getPaginatedList(array $data = [], int $perPage = 15)
    {
        return $this->listingRepository->getPaginatedList($data, $perPage);
    }

    public function getFastBrowseListings(array $data = [], int $perPage = 15)
    {
        $filters = $data['filters'] ?? [];
        $cacheKey = $this->generateCacheKey($filters, $perPage);

        return Cache::remember($cacheKey, 300, function () use ($data, $perPage) {
            return $this->listingRepository->getPaginatedList($data, $perPage);
        });
    }

    public function show(int $id)
    {
        $cacheKey = "listing:{$id}";

        return Cache::remember($cacheKey, 600, function () use ($id) {
            return $this->listingRepository->show($id);
        });
    }

    public function create(array $data)
    {
        return $this->listingRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        return $this->listingRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->listingRepository->delete($id);
    }


    public function searchListings(array $searchParams, int $perPage = 15)
    {
        $data = [
            'filters' => $this->buildFiltersFromSearch($searchParams),
            'search' => $searchParams['search'] ?? null,
            'sort' => $this->buildSortFromSearch($searchParams)
        ];
        return $this->getFastBrowseListings($data, $perPage);
    }

    public function getPopularMakes(array $data = []): array
    {
        $countryCode = $data['filters']['country_code'] ?? null;
        $cacheKey = "popular_makes:" . ($countryCode ?: 'all');

        return Cache::remember($cacheKey, 3600, function () use ($data) {
            return $this->listingRepository->list($data)
                ->selectRaw('make, COUNT(*) as count')
                ->groupBy('make')
                ->orderBy('count', 'desc')
                ->limit(20)
                ->pluck('count', 'make')
                ->toArray();
        });
    }

    public function updatePrice(int $id, array $data): mixed
    {
        $newPriceCents = (int) round($data['price'] * 100);

        // Get the listing first to access its properties
        $listing = $this->listingRepository->show($id);

        // Update the price directly - observer will handle event creation
        $listing->update(['price_cents' => $newPriceCents]);

        return $listing->fresh(['dealer']);
    }

    public function updateStatus(int $id, array $data): mixed
    {
        // Get the listing first to access its properties
        $listing = $this->listingRepository->show($id);

        // Update the status directly - observer will handle event creation
        $listing->update(['status' => $data['status']]);

        return $listing->fresh(['dealer']);
    }

    private function generateCacheKey(array $filters, int $perPage): string
    {
        ksort($filters);
        $filterString = http_build_query($filters);
        return "fast_browse:" . md5($filterString . ":page:{$perPage}");
    }

    private function buildFiltersFromSearch(array $searchParams): array
    {
        $filters = [];

        if (!empty($searchParams['country'])) {
            $filters['country_code'] = strtoupper($searchParams['country']);
        }

        if (!empty($searchParams['make'])) {
            $filters['make'] = $searchParams['make'];
        }

        if (!empty($searchParams['model'])) {
            $filters['model'] = $searchParams['model'];
        }

        if (!empty($searchParams['min_price'])) {
            $filters['min_price'] = (int) round($searchParams['min_price'] * 100);
        }

        if (!empty($searchParams['max_price'])) {
            $filters['max_price'] = (int) round($searchParams['max_price'] * 100);
        }

        if (!empty($searchParams['min_year'])) {
            $filters['min_year'] = (int) $searchParams['min_year'];
        }

        if (!empty($searchParams['max_year'])) {
            $filters['max_year'] = (int) $searchParams['max_year'];
        }

        if (!empty($searchParams['city'])) {
            $filters['city'] = $searchParams['city'];
        }

        if (!empty($searchParams['sort_by'])) {
            $filters['sort_by'] = $searchParams['sort_by'];
        }

        if (!empty($searchParams['sort_direction'])) {
            $filters['sort_direction'] = $searchParams['sort_direction'];
        }

        return $filters;
    }

    private function buildSortFromSearch(array $searchParams): array
    {
        $sort = [];

        if (!empty($searchParams['sort_by'])) {
            $column = $searchParams['sort_by'];
            $direction = $searchParams['sort_direction'] ?? 'desc';
            $sort[$column] = $direction;
        } else {
            $sort['listed_at'] = 'desc';
        }

        return $sort;
    }

    public function getFilteredListings(array $filters, int $perPage = 15): mixed
    {
        // Build cache key based on filters
        $cacheKey = $this->generateCacheKey($filters, $perPage);

        return Cache::remember($cacheKey, 300, function () use ($filters, $perPage) {
            return $this->listingRepository->getFastBrowseData($filters)->paginate($perPage);
        });
    }

    public function getMakeModels(string $make = null): array
    {
        $cacheKey = "make_models:" . ($make ? $make : 'all');

        return Cache::remember($cacheKey, 3600, function () use ($make) {
            $query = $this->listingRepository->getActiveListing();

            if ($make) {
                $query->where('make', $make);
            }

            return $query->select('make', 'model')
                ->distinct()
                ->orderBy('make')
                ->orderBy('model')
                ->get()
                ->groupBy('make')
                ->map(function ($items) {
                    return $items->pluck('model')->unique()->sort()->values();
                })
                ->toArray();
        });
    }

    public function getListingStatistics(string $countryCode = null): array
    {
        $cacheKey = "listing_stats:" . ($countryCode ?: 'all');

        return Cache::remember($cacheKey, 1800, function () use ($countryCode) {
            $query = $this->listingRepository->getActiveListing();

            if ($countryCode) {
                $query->where('country_code', $countryCode);
            }

            return [
                'total_listings' => $query->count(),
                'average_price' => round($query->avg('price_cents') / 100, 2),
                'price_range' => [
                    'min' => round($query->min('price_cents') / 100, 2),
                    'max' => round($query->max('price_cents') / 100, 2),
                ],
                'year_range' => [
                    'min' => $query->min('year'),
                    'max' => $query->max('year'),
                ],
                'total_dealers' => $query->distinct('dealer_id')->count('dealer_id'),
            ];
        });
    }
}