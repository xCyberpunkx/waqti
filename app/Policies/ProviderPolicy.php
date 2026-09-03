<?php

namespace App\Policies;

use App\Models\Provider;
use App\Models\User;

/**
 * Every business mutation is authorized on the server — frontend
 * permissions are UX only. See SOURCE_OF_TRUTH.md §2.2.
 */
class ProviderPolicy
{
    /**
     * A user may only view/manage the provider record they own.
     */
    public function view(User $user, Provider $provider): bool
    {
        return $user->id === $provider->user_id;
    }

    /**
     * A user may only update the provider record they own.
     */
    public function update(User $user, Provider $provider): bool
    {
        return $user->id === $provider->user_id;
    }
}
