<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $adminId = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required','email','unique:admins,email'],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'is_super_admin' => ['sometimes', 'boolean'],
        ];
    }
}
