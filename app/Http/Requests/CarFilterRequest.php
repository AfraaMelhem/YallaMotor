<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CarFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'make' => 'sometimes|string|max:50',
            'model' => 'sometimes|string|max:50',
            'year_min' => 'sometimes|integer|min:1900|max:' . (date('Y') + 1),
            'year_max' => 'sometimes|integer|min:1900|max:' . (date('Y') + 1),
            'price_min_cents' => 'sometimes|integer|min:0|max:99999999',
            'price_max_cents' => 'sometimes|integer|min:0|max:99999999',
            'mileage_max_km' => 'sometimes|integer|min:0|max:9999999',
            'country_code' => 'sometimes|string|size:2',
            'status' => 'sometimes|string|in:active,sold,hidden',
            'city' => 'sometimes|string|max:100',
            'sort_by' => 'sometimes|string|in:price_cents,year,mileage_km,listed_at,make,model',
            'sort_direction' => 'sometimes|string|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:50',
            'include_facets' => 'sometimes|boolean',
        ];
    }

    public function prepareForValidation(): void
    {
        // Set default status to active if not provided
        if (!$this->has('status')) {
            $this->merge(['status' => 'active']);
        }

        // Convert country code to uppercase
        if ($this->has('country_code')) {
            $this->merge(['country_code' => strtoupper($this->input('country_code'))]);
        }

        // Set default sorting
        if (!$this->has('sort_by')) {
            $this->merge([
                'sort_by' => 'listed_at',
                'sort_direction' => 'desc'
            ]);
        }

        // Ensure year_min <= year_max
        if ($this->has('year_min') && $this->has('year_max')) {
            $yearMin = $this->input('year_min');
            $yearMax = $this->input('year_max');

            if ($yearMin > $yearMax) {
                $this->merge([
                    'year_min' => $yearMax,
                    'year_max' => $yearMin
                ]);
            }
        }

        // Ensure price_min_cents <= price_max_cents
        if ($this->has('price_min_cents') && $this->has('price_max_cents')) {
            $priceMin = $this->input('price_min_cents');
            $priceMax = $this->input('price_max_cents');

            if ($priceMin > $priceMax) {
                $this->merge([
                    'price_min_cents' => $priceMax,
                    'price_max_cents' => $priceMin
                ]);
            }
        }

        // Convert include_facets to boolean
        if ($this->has('include_facets')) {
            $this->merge(['include_facets' => filter_var($this->input('include_facets'), FILTER_VALIDATE_BOOLEAN)]);
        }
    }

    public function messages(): array
    {
        return [
            'year_min.min' => 'Year must be at least 1900.',
            'year_max.max' => 'Year cannot be more than next year.',
            'country_code.size' => 'Country code must be exactly 2 characters.',
            'status.in' => 'Status must be one of: active, sold, hidden.',
            'sort_by.in' => 'Sort field must be one of: price_cents, year, mileage_km, listed_at, make, model.',
            'sort_direction.in' => 'Sort direction must be either asc or desc.',
            'per_page.max' => 'Results per page cannot exceed 50.',
        ];
    }
}