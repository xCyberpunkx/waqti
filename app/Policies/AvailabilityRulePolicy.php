<?php

namespace App\Policies;

use App\Models\AvailabilityRule;
use App\Models\User;

/**
 * Every business mutation is authorized on the server — frontend
 * permissions are UX only. See SOURCE_OF_TRUTH.md §2.2.
 */
class AvailabilityRulePolicy
{
    public function view(User $user, AvailabilityRule $rule): bool
    {
        return $user->provider?->id === $rule->provider_id;
    }

    public function update(User $user, AvailabilityRule $rule): bool
    {
        return $user->provider?->id === $rule->provider_id;
    }

    public function delete(User $user, AvailabilityRule $rule): bool
    {
        return $user->provider?->id === $rule->provider_id;
    }
}
