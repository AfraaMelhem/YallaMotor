<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Builder;

interface ListingRepositoryInterface extends BaseRepositoryInterface
{
    public function getActiveListing(): Builder;

    public function findByMakeAndModel(string $make, string $model): Builder;

    public function findByCountryCode(string $countryCode): Builder;

    public function findByPriceRange(int $minPrice = null, int $maxPrice = null): Builder;

    public function findByYearRange(int $minYear = null, int $maxYear = null): Builder;

    public function getFastBrowseData(array $filters = []): Builder;

    public function updatePrice(int $id, int $newPriceCents): mixed;

    public function updateStatus(int $id, string $newStatus): mixed;
}