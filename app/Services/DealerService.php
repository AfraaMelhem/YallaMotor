<?php

namespace App\Services;

use App\Repositories\Interfaces\DealerRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class DealerService
{
    public function __construct(
        private DealerRepositoryInterface $dealerRepository
    ) {}

    public function getAllDealers(int $perPage = 15)
    {
        return $this->dealerRepository->getPaginatedList([], $perPage);
    }

    public function getDealerById(int $id)
    {
        $cacheKey = "dealer:{$id}";

        return Cache::remember($cacheKey, 3600, function () use ($id) {
            return $this->dealerRepository->show($id);
        });
    }

    public function getDealersByCountry(string $countryCode)
    {
        $cacheKey = "dealers_by_country:{$countryCode}";

        return Cache::remember($cacheKey, 1800, function () use ($countryCode) {
            return $this->dealerRepository->findByCountryCode($countryCode);
        });
    }

    public function createDealer(array $data)
    {
        $dealer = $this->dealerRepository->create($data);

        Cache::forget("dealers_by_country:{$dealer->country_code}");

        return $dealer;
    }

    public function updateDealer(int $id, array $data)
    {
        $dealer = $this->dealerRepository->update($id, $data);

        Cache::forget("dealer:{$id}");
        Cache::forget("dealers_by_country:{$dealer->country_code}");

        return $dealer;
    }
}