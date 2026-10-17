<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The optional "about you" fields. Every one of them may be cleared, so the
 * rules are nullable throughout -- the only hard limits are length, a real
 * image for the photo, and a http(s) address for the link.
 */
class PublicProfileUpdateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072', 'dimensions:min_width=100,min_height=100'],
            'remove_avatar' => ['nullable', 'boolean'],
            'headline' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:600'],
            'location' => ['nullable', 'string', 'max:120'],
            // Accept "piseth.design" as readily as the full address; prepareForValidation
            // has already added the scheme by the time this runs.
            'website' => ['nullable', 'string', 'max:255', 'url:http,https'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $website = trim((string) $this->input('website'));

        if ($website !== '' && ! preg_match('#^https?://#i', $website)) {
            $this->merge(['website' => 'https://'.$website]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'avatar.dimensions' => 'The photo should be at least 100 × 100 pixels.',
            'avatar.max' => 'The photo may not be larger than 3 MB.',
            'website.url' => 'That does not look like a web address.',
        ];
    }
}
