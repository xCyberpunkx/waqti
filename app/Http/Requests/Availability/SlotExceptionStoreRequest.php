<?php

namespace App\Http\Requests\Availability;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SlotExceptionStoreRequest extends FormRequest
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
     * The closed-vs-override-hours mutual exclusion is enforced in
     * `withValidator()` below, not with `prohibited_if:is_closed,true`
     * here — that rule's behavior depends on Laravel's internal type
     * coercion between the boolean `is_closed` input and the string
     * `"true"` rule parameter, which isn't worth relying on when an
     * explicit check is just as short and fully unambiguous.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'after_or_equal:today'],
            'is_closed' => ['required', 'boolean'],
            'override_starts_at' => [
                'nullable', 'date_format:H:i', 'required_with:override_ends_at',
            ],
            'override_ends_at' => [
                'nullable', 'date_format:H:i', 'after:override_starts_at',
                'required_with:override_starts_at',
            ],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->boolean('is_closed')) {
                return;
            }

            if ($this->filled('override_starts_at') || $this->filled('override_ends_at')) {
                $message = 'Override hours cannot be set when the date is marked closed.';
                $validator->errors()->add('override_starts_at', $message);
                $validator->errors()->add('override_ends_at', $message);
            }
        });
    }
}
