<?php

namespace App\Http\Requests\Availability;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Creates or updates the rule for a given weekday. A provider must
 * exist before availability can be set — see
 * AvailabilityController::edit().
 */
class AvailabilityRuleUpsertRequest extends FormRequest
{
    /**
     * Authorize on the server, never trust the frontend — see
     * SOURCE_OF_TRUTH.md §2.2.
     */
    public function authorize(): bool
    {
        return $this->user()?->provider !== null;
    }

    /**
     * Deliberately excludes `provider_id` — always relation-derived in
     * the controller, never request input (SECURITY.md §7).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'weekday' => ['required', 'integer', 'between:0,6'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'slot_length_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
