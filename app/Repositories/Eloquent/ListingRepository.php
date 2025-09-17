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
        $query = $this->model->active()->with('dealer');

        if (!empty($filters['country_code'])) {
            $query->byCountry($filters['country_code']);
        }

        if (!empty($filters['make'])) {
            $query->byMake($filters['make']);
        }

        if (!empty($filters['model'])) {
            $query->byModel($filters['model']);
        }

        if (!empty($filters['min_price']) || !empty($filters['max_price'])) {
            $query->priceRange($filters['min_price'] ?? null, $filters['max_price'] ?? null);
        }

        if (!empty($filters['min_year']) || !empty($filters['max_year'])) {
            $query->yearRange($filters['min_year'] ?? null, $filters['max_year'] ?? null);
        }

        if (!empty($filters['city'])) {
            $query->where('city', 'like', '%' . $filters['city'] . '%');
        }

        $sortBy = $filters['sort_by'] ?? 'listed_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        switch ($sortBy) {
            case 'price':
                $query->orderBy('price_cents', $sortDirection);
                break;
            case 'year':
                $query->orderBy('year', $sortDirection);
                break;
            case 'mileage':
                $query->orderBy('mileage_km', $sortDirection);
                break;
            default:
                $query->orderBy('listed_at', $sortDirection);
        }

        return $query;
    }

    public function updatePrice(int $id, int $newPriceCents): mixed
    {
        $listing = $this->show($id);
        $oldPrice = $listing->price_cents;

        if ($oldPrice !== $newPriceCents) {
            $listing->update(['price_cents' => $newPriceCents]);
            ListingEvent::createPriceChangedEvent($id, $oldPrice, $newPriceCents);
        }

        return $listing->fresh();
    }

    public function updateStatus(int $id, string $newStatus): mixed
    {
        $listing = $this->show($id);
        $oldStatus = $listing->status;

        if ($oldStatus !== $newStatus) {
            $listing->update(['status' => $newStatus]);
            ListingEvent::createStatusChangedEvent($id, $oldStatus, $newStatus);
        }

        return $listing->fresh();
    }
}