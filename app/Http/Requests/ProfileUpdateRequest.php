<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\NotRestrictedName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', new NotRestrictedName],
            'phone' => $this->phoneRules(),
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }

    /**
     * Phone numbers may only contain digits. That format is enforced on any
     * NEW or CHANGED value; it is not re-enforced on a value submitted
     * unchanged, so a number already saved in an older format (e.g.
     * "+855 12 000 001") doesn't start failing validation just because the
     * user edited something else on this form and left it untouched.
     *
     * @return array<int, mixed>
     */
    private function phoneRules(): array
    {
        $rules = ['nullable', 'string', 'max:32'];

        $submitted = $this->input('phone');
        $changed = $submitted !== null && $submitted !== $this->user()->phone;

        if ($changed) {
            // \A...\z (not ^...$) so a trailing newline can't slip through.
            $rules[] = 'regex:/\A[0-9]+\z/';
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'The phone number may only contain digits (0–9) — no spaces, +, dashes, letters, or symbols.',
        ];
    }
}
