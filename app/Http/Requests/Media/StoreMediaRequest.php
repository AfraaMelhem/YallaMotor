<?php

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
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
        return [
            'filename' => 'required|string|max:255',
            'mime_type' => 'required|string|max:100',
            'file_path' => 'required|string|max:500',
            'mediable_id' => 'required|integer',
            'mediable_type' => 'required|string',
            'order' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'file' => 'nullable|file',
        ];
    }
}
