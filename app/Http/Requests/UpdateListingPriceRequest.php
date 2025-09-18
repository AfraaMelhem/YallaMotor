<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateListingPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'price' => [
                'required',
                'numeric',
                'min:100',
                'max:1000000',
                'decimal:0,2'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'price.required' => 'Price is required',
            'price.numeric' => 'Price must be a valid number',
            'price.min' => 'Price cannot be less than $100',
            'price.max' => 'Price cannot exceed $1,000,000',
            'price.decimal' => 'Price can have maximum 2 decimal places'
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'price' => is_numeric($this->price) ? round($this->price, 2) : $this->price
        ]);
    }
}
