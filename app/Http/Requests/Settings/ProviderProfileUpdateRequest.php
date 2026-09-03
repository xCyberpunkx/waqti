<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProviderProfileUpdateRequest extends FormRequest
{
    /**
     * Authorize on the server, never trust the frontend — see
     * SOURCE_OF_TRUTH.md §2.2. A user may create their own provider (no
     * provider exists for them yet) or update the one they already own;
     * they may never touch another user's provider.
     */
    public function authorize(): bool
    {
        $provider = $this->user()?->provider;

        return $provider === null || $this->user()->can('update', $provider);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Deliberately excludes `user_id` and any WhatsApp credential
     * fields — those simply aren't in this form's rules, so
     * `validated()` never returns them and they can never reach
     * `Provider::fill()` through this request, regardless of what the
     * model allows (see SECURITY.md §7).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'business_category' => ['nullable', 'string', 'max:255'],
            'timezone' => ['required', 'timezone'],
        ];
    }
}
