<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class CreateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Check rate limiting before authorization
        $this->checkRateLimit();
        return true;
    }

    public function rules(): array
    {
        return [
            'listing_id' => 'required|integer|exists:listings,id',
            'name' => 'required|string|max:255|min:2',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'message' => 'nullable|string|max:1000',
            'source' => 'sometimes|string|in:api,website,mobile,social',
        ];
    }

    public function prepareForValidation(): void
    {
        // Set default source if not provided
        if (!$this->has('source')) {
            $this->merge(['source' => 'api']);
        }

        // Clean up phone number
        if ($this->has('phone')) {
            $phone = preg_replace('/[^\d+\-\(\)\s]/', '', $this->input('phone'));
            $this->merge(['phone' => $phone]);
        }

        // Normalize email
        if ($this->has('email')) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }

        // Clean up name
        if ($this->has('name')) {
            $this->merge(['name' => trim($this->input('name'))]);
        }

        // Add request metadata
        $this->merge([
            'ip_address' => $this->ip(),
            'user_agent' => $this->userAgent(),
        ]);
    }

    public function messages(): array
    {
        return [
            'listing_id.required' => 'A valid listing ID is required.',
            'listing_id.exists' => 'The specified listing does not exist.',
            'name.required' => 'Your name is required.',
            'name.min' => 'Name must be at least 2 characters long.',
            'email.required' => 'A valid email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'phone.max' => 'Phone number is too long.',
            'message.max' => 'Message cannot exceed 1000 characters.',
            'source.in' => 'Invalid source specified.',
        ];
    }

    private function checkRateLimit(): void
    {
        $key = $this->getRateLimitKey();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => ["Too many lead submissions. Please try again in {$seconds} seconds."],
            ])->status(429);
        }

        RateLimiter::hit($key, 3600); // 1 hour window
    }

    private function getRateLimitKey(): string
    {
        $email = $this->input('email', '');
        $ip = $this->ip();

        // Use both email and IP for rate limiting
        return "leads:{$ip}:{$email}";
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {

            // Validate listing is active
            if ($this->has('listing_id')) {
                $listing = \App\Models\Listing::find($this->input('listing_id'));
                if ($listing && $listing->status !== 'active') {
                $validator->errors()->add('listing_id', 'This listing is no longer available for leads.');
                }
            }
        });
    }
}
