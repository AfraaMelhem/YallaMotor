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

    public function getFastBrowseListings(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = $this->generateCacheKey($filters, $perPage);

        return Cache::remember($cacheKey, 300, function () use ($filters, $perPage) {
            return $this->listingRepository
                ->getFastBrowseData($filters)
                ->paginate($perPage);
        });
    }

    public function getListingById(int $id)
    {
        $cacheKey = "listing:{$id}";

        return Cache::remember($cacheKey, 600, function () use ($id) {
            return $this->listingRepository->show($id);
        });
    }

    public function searchListings(array $searchParams, int $perPage = 15): LengthAwarePaginator
    {
        $filters = $this->buildFiltersFromSearch($searchParams);
        return $this->getFastBrowseListings($filters, $perPage);
    }

    public function getPopularMakes(string $countryCode = null): array
    {
        $cacheKey = "popular_makes:" . ($countryCode ?: 'all');

        return Cache::remember($cacheKey, 3600, function () use ($countryCode) {
            $query = $this->listingRepository->getActiveListing();

            if ($countryCode) {
                $query->byCountry($countryCode);
            }

            return $query->selectRaw('make, COUNT(*) as count')
                ->groupBy('make')
                ->orderBy('count', 'desc')
                ->limit(20)
                ->pluck('count', 'make')
                ->toArray();
        });
    }

    public function updateListingPrice(int $id, float $newPrice): mixed
    {
        $newPriceCents = (int) round($newPrice * 100);
        $result = $this->listingRepository->updatePrice($id, $newPriceCents);

        $this->invalidateListingCache($id);

        return $result;
    }

    public function updateListingStatus(int $id, string $newStatus): mixed
    {
        $result = $this->listingRepository->updateStatus($id, $newStatus);

        $this->invalidateListingCache($id);

        return $result;
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

    private function invalidateListingCache(int $id): void
    {
        Cache::forget("listing:{$id}");
        Cache::flush(); // In production, implement more granular cache invalidation
    }
}