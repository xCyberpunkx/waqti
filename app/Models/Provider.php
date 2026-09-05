<?php

namespace App\Models;

use Database\Factories\ProviderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
#[Fillable([
    'name',
    'business_category',
    'timezone',
    'whatsapp_phone_number_id',
    'whatsapp_business_account_id',
    'whatsapp_access_token',
])]
#[Hidden(['whatsapp_access_token'])]
class Provider extends Model
{
    /** @use HasFactory<ProviderFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * The `encrypted` cast satisfies SECURITY.md §3 (encrypted at
     * rest). WhatsApp fields ARE model-fillable (needed for
     * `updateOrCreate()`/`fill()` in `ProviderController` to actually
     * set them — a prior version excluded them here, which silently
     * dropped every credential save). The real mass-assignment
     * boundary (SECURITY.md §7) is enforced one layer up, by
     * `ProviderProfileUpdateRequest` and
     * `ProviderWhatsappCredentialsUpdateRequest` only ever validating
     * (and therefore only ever passing through `validated()`) the
     * fields relevant to their own form — `user_id` is never
     * fillable/validated anywhere and is always relation-derived.
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

    /**
     * @return HasMany<AvailabilityRule, $this>
     */
    public function availabilityRules(): HasMany
    {
        return $this->hasMany(AvailabilityRule::class);
    }

    /**
     * @return HasMany<SlotException, $this>
     */
    public function slotExceptions(): HasMany
    {
        return $this->hasMany(SlotException::class);
    }

    /**
     * @return HasMany<Client, $this>
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return HasMany<InboundMessage, $this>
     */
    public function inboundMessages(): HasMany
    {
        return $this->hasMany(InboundMessage::class);
    }

    /**
     * @return HasMany<ConversationState, $this>
     */
    public function conversationStates(): HasMany
    {
        return $this->hasMany(ConversationState::class);
    }
}
