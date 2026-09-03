<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProviderWhatsappCredentialsUpdateRequest extends FormRequest
{
    /**
     * Kept as a separate form/request from general provider profile
     * fields deliberately — WhatsApp credentials are sensitive
     * (SECURITY.md §3) and shouldn't ride along with an unrelated
     * name/timezone update.
     */
    public function authorize(): bool
    {
        $provider = $this->user()?->provider;

        return $provider === null || $this->user()->can('update', $provider);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The access token is `sometimes` so an admin can update the phone
     * number IDs without being forced to re-paste the token every time,
     * but it's required the first time (when no provider row exists
     * yet, or the provider has no token stored).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $hasExistingToken = filled($this->user()?->provider?->whatsapp_access_token);

        return [
            'whatsapp_phone_number_id' => ['required', 'string', 'max:255'],
            'whatsapp_business_account_id' => ['required', 'string', 'max:255'],
            'whatsapp_access_token' => [
                $hasExistingToken ? 'sometimes' : 'required',
                'string',
            ],
        ];
    }
}
