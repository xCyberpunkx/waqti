<?php

namespace App\Policies;

use App\Models\SlotException;
use App\Models\User;

/**
 * Every business mutation is authorized on the server — frontend
 * permissions are UX only. See SOURCE_OF_TRUTH.md §2.2.
 */
class SlotExceptionPolicy
{
    public function view(User $user, SlotException $exception): bool
    {
        return $user->provider?->id === $exception->provider_id;
    }

    public function delete(User $user, SlotException $exception): bool
    {
        return $user->provider?->id === $exception->provider_id;
    }
}
