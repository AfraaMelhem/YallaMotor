<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dealer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'country_code',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function activeListings(): HasMany
    {
        return $this->hasMany(Listing::class)->where('status', 'active');
    }

    // Custom filter methods for the Filterable trait
    protected function filterByCountryCode(Builder $query, string $value): Builder
    {
        return $query->where('country_code', strtoupper($value));
    }

    protected function filterByName(Builder $query, string $value): Builder
    {
        return $query->where('name', 'LIKE', '%' . $value . '%');
    }

    protected function filterByHasActiveListings(Builder $query, bool $value): Builder
    {
        if ($value) {
            return $query->whereHas('listings', function (Builder $q) {
                $q->where('status', 'active');
            });
        }
        return $query;
    }
}
