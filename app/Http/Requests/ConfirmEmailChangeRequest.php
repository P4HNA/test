<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmEmailChangeRequest extends FormRequest
{
    /**
     * Puts this form's own validation failures (e.g. a missing/malformed
     * code) in the same "emailChange" error bag the controller uses for
     * incorrect/expired/used-up codes, so the view only ever has to check
     * one bag -- the same pattern as "userDeletion" on the delete-account
     * form.
     */
    protected $errorBag = 'emailChange';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'digits:6'],
        ];
    }
}
