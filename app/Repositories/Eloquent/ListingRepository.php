<?php

namespace App\Repositories\Eloquent;

use App\Models\Listing;
use App\Models\ListingEvent;
use App\Repositories\Interfaces\ListingRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class ListingRepository extends BaseRepository implements ListingRepositoryInterface
{
    public function __construct(Listing $model)
    {
        parent::__construct($model);
    }

    public function getActiveListing(): Builder
    {
        return $this->model->active();
    }

    public function findByMakeAndModel(string $make, string $model): Builder
    {
        return $this->model->byMake($make)->byModel($model);
    }

    public function findByCountryCode(string $countryCode): Builder
    {
        return $this->model->byCountry($countryCode);
    }

    public function findByPriceRange(int $minPrice = null, int $maxPrice = null): Builder
    {
        return $this->model->priceRange($minPrice, $maxPrice);
    }

    public function findByYearRange(int $minYear = null, int $maxYear = null): Builder
    {
        return $this->model->yearRange($minYear, $maxYear);
    }

    public function getFastBrowseData(array $filters = []): Builder
    {
        // Start with active listings and eager load dealer relationship
        $query = $this->model->active()->with('dealer');

        // Use the Filterable trait for filtering
        if (!empty($filters)) {
            $query = $this->applyFilters($query, $filters);
        }

        return $query;
    }

    public function updateWithFilters(array $filters, array $data): mixed
    {
        $query = $this->getFastBrowseData($filters);
        return $query->update($data);
    }

    public function getListingsByIds(array $ids): mixed
    {
        return $this->model->whereIn('id', $ids)->with('dealer')->get();
    }

    public function getRecentListings(int $days = 7, int $limit = 10): mixed
    {
        return $this->model->active()
            ->where('listed_at', '>=', now()->subDays($days))
            ->with('dealer')
            ->orderBy('listed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getSimilarListings(int $listingId, int $limit = 5): mixed
    {
        $listing = $this->show($listingId);

        return $this->model->active()
            ->where('id', '!=', $listingId)
            ->where('make', $listing->make)
            ->where('country_code', $listing->country_code)
            ->whereBetween('price_cents', [
                $listing->price_cents * 0.8,
                $listing->price_cents * 1.2
            ])
            ->with('dealer')
            ->orderBy('price_cents')
            ->limit($limit)
            ->get();
    }
}