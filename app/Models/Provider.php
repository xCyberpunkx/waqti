<?php

namespace App\Models;

use Database\Factories\ProviderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $business_category
 * @property string $timezone
 * @property string|null $whatsapp_phone_number_id
 * @property string|null $whatsapp_business_account_id
 * @property string|null $whatsapp_access_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'business_category', 'timezone'])]
#[Hidden(['whatsapp_access_token'])]
class Provider extends Model
{
    /** @use HasFactory<ProviderFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * WhatsApp credentials are never mass-assignable (see SECURITY.md
     * §7) — they're set explicitly through a dedicated action, not
     * through the general provider-profile update path. The
     * `encrypted` cast satisfies SECURITY.md §3 (encrypted at rest).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'whatsapp_access_token' => 'encrypted',
        ];
    }

    /**
     * The dashboard user who owns this provider.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
