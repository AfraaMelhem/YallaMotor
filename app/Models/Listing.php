<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Listing extends Model
{
    use HasFactory, Filterable;

    protected $fillable = [
        'dealer_id',
        'make',
        'model',
        'year',
        'price_cents',
        'mileage_km',
        'country_code',
        'city',
        'status',
        'listed_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'price_cents' => 'integer',
        'mileage_km' => 'integer',
        'listed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ListingEvent::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeByCountry(Builder $query, string $countryCode): Builder
    {
        return $query->where('country_code', $countryCode);
    }

    public function scopeByMake(Builder $query, string $make): Builder
    {
        return $query->where('make', $make);
    }

    public function scopeByModel(Builder $query, string $model): Builder
    {
        return $query->where('model', $model);
    }

    public function scopePriceRange(Builder $query, int $minPrice = null, int $maxPrice = null): Builder
    {
        if ($minPrice) {
            $query->where('price_cents', '>=', $minPrice);
        }
        if ($maxPrice) {
            $query->where('price_cents', '<=', $maxPrice);
        }
        return $query;
    }

    public function scopeYearRange(Builder $query, int $minYear = null, int $maxYear = null): Builder
    {
        if ($minYear) {
            $query->where('year', '>=', $minYear);
        }
        if ($maxYear) {
            $query->where('year', '<=', $maxYear);
        }
        return $query;
    }

    protected function getPriceAttribute(): float
    {
        return $this->price_cents / 100;
    }

    // Custom filter methods for the Filterable trait
    protected function filterByYearMin(Builder $query, int $value): Builder
    {
        return $query->where('year', '>=', $value);
    }

    protected function filterByYearMax(Builder $query, int $value): Builder
    {
        return $query->where('year', '<=', $value);
    }

    protected function filterByPriceMinCents(Builder $query, int $value): Builder
    {
        return $query->where('price_cents', '>=', $value);
    }

    protected function filterByPriceMaxCents(Builder $query, int $value): Builder
    {
        return $query->where('price_cents', '<=', $value);
    }

    protected function filterByMileageMaxKm(Builder $query, int $value): Builder
    {
        return $query->where('mileage_km', '<=', $value);
    }

    protected function filterByPriceRange(Builder $query, array $value): Builder
    {
        if (isset($value['min'])) {
            $query->where('price_cents', '>=', $value['min'] * 100);
        }
        if (isset($value['max'])) {
            $query->where('price_cents', '<=', $value['max'] * 100);
        }
        return $query;
    }

    protected function filterByYearRange(Builder $query, array $value): Builder
    {
        if (isset($value['min'])) {
            $query->where('year', '>=', $value['min']);
        }
        if (isset($value['max'])) {
            $query->where('year', '<=', $value['max']);
        }
        return $query;
    }

    protected function filterByMileageRange(Builder $query, array $value): Builder
    {
        if (isset($value['min'])) {
            $query->where('mileage_km', '>=', $value['min']);
        }
        if (isset($value['max'])) {
            $query->where('mileage_km', '<=', $value['max']);
        }
        return $query;
    }

    protected function filterByMake(Builder $query, string $value): Builder
    {
        return $query->where('make', 'LIKE', '%' . $value . '%');
    }

    protected function filterByModel(Builder $query, string $value): Builder
    {
        return $query->where('model', 'LIKE', '%' . $value . '%');
    }

    protected function filterByCountryCode(Builder $query, string $value): Builder
    {
        return $query->where('country_code', strtoupper($value));
    }

    protected function filterByCity(Builder $query, string $value): Builder
    {
        return $query->where('city', 'LIKE', '%' . $value . '%');
    }

    protected function filterByStatus(Builder $query, string $value): Builder
    {
        return $query->where('status', $value);
    }

    protected function filterByDealer(Builder $query, $value): Builder
    {
        if (is_array($value)) {
            return $query->whereIn('dealer_id', $value);
        }
        return $query->where('dealer_id', $value);
    }

    protected function filterByListedAfter(Builder $query, string $value): Builder
    {
        return $query->where('listed_at', '>=', $value);
    }

    protected function filterByListedBefore(Builder $query, string $value): Builder
    {
        return $query->where('listed_at', '<=', $value);
    }
}
