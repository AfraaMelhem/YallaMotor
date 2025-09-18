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

    public function updateWithFilters(array $filters, array $data): mixed;

    public function getListingsByIds(array $ids): mixed;

    public function getRecentListings(int $days = 7, int $limit = 10): mixed;

    public function getSimilarListings(int $listingId, int $limit = 5): mixed;
}