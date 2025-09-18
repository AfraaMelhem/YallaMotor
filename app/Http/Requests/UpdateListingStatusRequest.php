<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateListingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in(['active', 'sold', 'hidden'])
            ],
            'reason' => [
                'sometimes',
                'string',
                'max:500'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status is required',
            'status.in' => 'Status must be one of: active, sold, hidden',
            'reason.max' => 'Reason cannot exceed 500 characters'
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => strtolower(trim($this->status ?? '')),
            'reason' => trim($this->reason ?? '')
        ]);
    }
}
