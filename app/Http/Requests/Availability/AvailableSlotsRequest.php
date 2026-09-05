<?php

namespace App\Http\Requests\Availability;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class AvailableSlotsRequest extends FormRequest
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
     * Capped at 14 days per request to keep this a cheap read — the
     * booking flow (Step 4) will call this with small ranges anyway.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from' => ['required', 'date', 'after_or_equal:today'],
            'to' => [
                'required',
                'date',
                'after_or_equal:from',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $this->filled('from')) {
                        return;
                    }

                    if (Carbon::parse($this->input('from'))->diffInDays(Carbon::parse($value)) > 13) {
                        $fail('The date range may not span more than 14 days.');
                    }
                },
            ],
        ];
    }
}
