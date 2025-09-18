<?php

namespace App\Http\Resources;

use App\Traits\ImageUrlFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseResource extends JsonResource
{
    use ImageUrlFormatter;

    public function toArray(Request $request): array
    {
        if (is_null($this->resource)) {
            return [];
        }

        $data = [];

        $hidden = ['password', 'remember_token'];

        if (!is_null($this->id)) {
            $data['id'] = $this->id;
        }

        if (method_exists($this->resource, 'getFillable')) {
            foreach ($this->resource->getFillable() as $attribute) {
                if (!in_array($attribute, $hidden) && !is_null($this->{$attribute})) {

                    if (str_contains($attribute, 'image') || $attribute == 'logo') {
                        $data[$attribute] =url($this->{$attribute});
                    } else {
                        $data[$attribute] = $this->{$attribute};
                    }
                }
            }
        }

        if (property_exists($this->resource, 'translatable')) {
            foreach ($this->resource->translatable as $field) {
                if (!is_null($this->{$field})) {
                    $data[$field] = $this->getTranslation($field, app()->getLocale());
                }
            }
        }


        // Enhanced car marketplace specific fields
        if (isset($this->resource) && $this->resource instanceof \App\Models\Listing) {
            $this->enhanceListingData($data);
        }

        if (isset($this->resource) && $this->resource instanceof \App\Models\Dealer) {
            $this->enhanceDealerData($data);
        }

        if (isset($this->resource) && $this->resource instanceof \App\Models\ListingEvent) {
            $this->enhanceListingEventData($data);
        }

        return $data;
    }

    private function enhanceListingData(array &$data): void
    {
        // Transform price_cents to price in dollars
        if (isset($data['price_cents'])) {
            $data['price'] = $data['price_cents'] / 100;
            $data['price_formatted'] = '$' . number_format($data['price_cents'] / 100, 2);
            unset($data['price_cents']); // Remove the raw cents value
        }

        // Add mileage in different units
        if (isset($data['mileage_km'])) {
            $data['mileage_miles'] = round($data['mileage_km'] * 0.621371, 2);
        }

        // Add car age
        if (isset($data['year'])) {
            $data['age_years'] = now()->year - $data['year'];
        }

        // Add status color for frontend
        if (isset($data['status'])) {
            $data['status_color'] = match($data['status']) {
                'active' => 'green',
                'sold' => 'blue',
                'hidden' => 'gray',
                default => 'gray'
            };
        }

        // Add dealer information if loaded
        if ($this->relationLoaded('dealer')) {
            $data['dealer'] = new BaseResource($this->dealer);
        }

        // Add recent events if loaded
        if ($this->relationLoaded('events')) {
            $data['recent_events'] = $this->events->take(5)->map(function ($event) {
                return new BaseResource($event);
            });
            $data['events_count'] = $this->events->count();
        }

        // Format timestamps
        if (isset($data['listed_at'])) {
            $data['listed_at'] = $this->listed_at?->toISOString();
        }
    }

    private function enhanceDealerData(array &$data): void
    {
        // Add computed fields
        if ($this->relationLoaded('listings')) {
            $data['listings_count'] = $this->listings->count();
            $data['active_listings_count'] = $this->listings->where('status', 'active')->count();

            // Add recent listings
            $data['recent_listings'] = $this->listings
                ->where('status', 'active')
                ->sortByDesc('listed_at')
                ->take(3)
                ->map(function ($listing) {
                    return new BaseResource($listing);
                })->values();
        }

        // Country name mapping (you could move this to a config file)
        $countries = [
            'US' => 'United States',
            'CA' => 'Canada',
            'GB' => 'United Kingdom',
            'DE' => 'Germany',
            'FR' => 'France',
            'AU' => 'Australia',
            'AE' => 'United Arab Emirates',
            'SA' => 'Saudi Arabia'
        ];

        if (isset($data['country_code'])) {
            $data['country_name'] = $countries[$data['country_code']] ?? $data['country_code'];
        }
    }

    private function enhanceListingEventData(array &$data): void
    {
        // Add formatted event message
        if (isset($data['type']) && isset($data['payload'])) {
            $data['message'] = match($data['type']) {
                'price_changed' => sprintf(
                    'Price changed from $%s to $%s',
                    number_format($data['payload']['old_price_cents'] / 100, 2),
                    number_format($data['payload']['new_price_cents'] / 100, 2)
                ),
                'status_changed' => sprintf(
                    'Status changed from %s to %s',
                    ucfirst($data['payload']['old_status']),
                    ucfirst($data['payload']['new_status'])
                ),
                default => 'Unknown event'
            };
        }

        // Add listing information if loaded
        if ($this->relationLoaded('listing')) {
            $data['listing'] = new BaseResource($this->listing);
        }

        // Format timestamp
        if (isset($data['created_at'])) {
            $data['created_at'] = $this->created_at?->toISOString();
            $data['time_ago'] = $this->created_at?->diffForHumans();
        }
    }
}
