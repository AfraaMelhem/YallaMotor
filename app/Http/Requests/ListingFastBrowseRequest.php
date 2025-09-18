<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListingFastBrowseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $currentYear = date('Y');

        return [
            'country' => [
                'sometimes',
                'string',
                'size:2',
                'regex:/^[A-Z]{2}$/',
                'in:US,CA,GB,DE,FR,AU,AE,SA,JP,KR,CN,IN,BR,MX,ES,IT,NL,SE,NO,DK'
            ],
            'make' => [
                'sometimes',
                'string',
                'max:100'
            ],
            'model' => [
                'sometimes',
                'string',
                'max:100'
            ],
            'min_price' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:1000000'
            ],
            'max_price' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:1000000',
                'gte:min_price'
            ],
            'min_year' => [
                'sometimes',
                'integer',
                'min:1900',
                'max:' . ($currentYear + 1)
            ],
            'max_year' => [
                'sometimes',
                'integer',
                'min:1900',
                'max:' . ($currentYear + 1),
                'gte:min_year'
            ],
            'min_mileage' => [
                'sometimes',
                'integer',
                'min:0',
                'max:1000000'
            ],
            'max_mileage' => [
                'sometimes',
                'integer',
                'min:0',
                'max:1000000',
                'gte:min_mileage'
            ],
            'city' => [
                'sometimes',
                'string',
                'max:100'
            ],
            'sort_by' => [
                'sometimes',
                'in:price,year,mileage,listed_at'
            ],
            'sort_direction' => [
                'sometimes',
                'in:asc,desc'
            ],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100'
            ],
            'status' => [
                'sometimes',
                'in:active,sold,hidden'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'country.in' => 'Invalid country code provided',
            'make.alpha_dash' => 'Make can only contain letters, numbers, dashes and underscores',
            'min_price.max' => 'Minimum price cannot exceed $1,000,000',
            'max_price.gte' => 'Maximum price must be greater than or equal to minimum price',
            'max_year.gte' => 'Maximum year must be greater than or equal to minimum year',
            'max_mileage.gte' => 'Maximum mileage must be greater than or equal to minimum mileage',
            'per_page.max' => 'Results per page cannot exceed 100'
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('country') && $this->country) {
            $data['country'] = strtoupper($this->country);
        }

        if ($this->has('make') && $this->make) {
            $data['make'] = ucfirst(strtolower(trim($this->make)));
        }

        if ($this->has('model') && $this->model) {
            $data['model'] = trim($this->model);
        }

        if ($this->has('city') && $this->city) {
            $data['city'] = trim($this->city);
        }

        $data['sort_direction'] = $this->sort_direction ?: 'desc';
        $data['sort_by'] = $this->sort_by ?: 'listed_at';
        $data['per_page'] = min($this->per_page ?: 15, 100);

        $this->merge($data);
    }
}
