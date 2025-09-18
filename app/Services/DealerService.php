<?php

namespace App\Services;

use App\Repositories\Interfaces\DealerRepositoryInterface;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;

class DealerService
{
    public function __construct(
        protected DealerRepositoryInterface $dealerRepository,
        private CacheService $cacheService
    ) {}

    public function getPaginatedList(array $data = [], int $perPage = 15)
    {
        return $this->dealerRepository->getPaginatedList($data, $perPage);
    }

    public function show(int $id)
    {
        $cacheKey = "dealer:{$id}";
        $tags = ["dealer:{$id}"];

        return $this->cacheService->remember($cacheKey, $tags, 3600, function () use ($id) {
            return $this->dealerRepository->show($id);
        });
    }

    public function showWithRelations(int $id)
    {
        $cacheKey = "dealer_full:{$id}";
        $tags = ["dealer:{$id}"];

        return $this->cacheService->remember($cacheKey, $tags, 3600, function () use ($id) {
            return $this->dealerRepository->show($id)->load(['listings' => function($query) {
                $query->where('status', 'active')->orderBy('listed_at', 'desc')->limit(5);
            }]);
        });
    }

    public function create(array $data)
    {
        $dealer = $this->dealerRepository->create($data);

        // Clear relevant caches
        Cache::forget("dealers_by_country:{$dealer->country_code}");
        Cache::tags(['dealers', 'dealers_by_country', 'dealer_statistics'])->flush();

        return $dealer;
    }

    public function update(int $id, array $data)
    {
        $dealer = $this->dealerRepository->update($id, $data);

        // Clear relevant caches
        Cache::forget("dealer:{$id}");
        Cache::forget("dealer_full:{$id}");
        Cache::forget("dealers_by_country:{$dealer->country_code}");
        Cache::tags(['dealers', 'dealers_by_country', 'dealer_statistics'])->flush();

        return $dealer;
    }

    public function delete(int $id): bool
    {
        $dealer = $this->dealerRepository->show($id);
        $result = $this->dealerRepository->delete($id);

        // Clear relevant caches
        Cache::forget("dealer:{$id}");
        Cache::forget("dealer_full:{$id}");
        Cache::forget("dealers_by_country:{$dealer->country_code}");
        Cache::tags(['dealers', 'dealers_by_country', 'dealer_statistics'])->flush();

        return $result;
    }

    public function getDealersByCountry(string $countryCode)
    {
        $cacheKey = "dealers_by_country:{$countryCode}";
        $tags = ['dealers_by_country', "country:{$countryCode}"];

        return $this->cacheService->remember($cacheKey, $tags, 1800, function () use ($countryCode) {
            return $this->dealerRepository->findByCountryCode($countryCode);
        });
    }

    public function getDealerStatistics(array $data = []): array
    {
        $cacheKey = 'dealer_statistics:' . md5(serialize($data));
        $tags = ['dealer_statistics'];

        return $this->cacheService->remember($cacheKey, $tags, 3600, function () use ($data) {
            return [
                'total_dealers' => $this->dealerRepository->list($data)->count(),
                'dealers_by_country' => $this->dealerRepository->list($data)
                    ->selectRaw('country_code, COUNT(*) as count')
                    ->groupBy('country_code')
                    ->pluck('count', 'country_code')
                    ->toArray(),
                'active_dealers_with_listings' => $this->dealerRepository->list($data)
                    ->whereHas('listings', function($query) {
                        $query->where('status', 'active');
                    })
                    ->count()
            ];
        });
    }
}