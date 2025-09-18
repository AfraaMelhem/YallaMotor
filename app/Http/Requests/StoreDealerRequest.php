<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDealerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2'
            ],
            'country_code' => [
                'required',
                'string',
                'size:2',
                'regex:/^[A-Z]{2}$/',
                'in:US,CA,GB,DE,FR,AU,AE,SA,JP,KR,CN,IN,BR,MX,ES,IT,NL,SE,NO,DK'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Dealer name is required',
            'name.min' => 'Dealer name must be at least 2 characters',
            'country_code.required' => 'Country code is required',
            'country_code.size' => 'Country code must be exactly 2 characters',
            'country_code.regex' => 'Country code must be uppercase letters only',
            'country_code.in' => 'Invalid country code provided'
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'country_code' => strtoupper($this->country_code ?? ''),
            'name' => trim($this->name ?? '')
        ]);
    }
}
