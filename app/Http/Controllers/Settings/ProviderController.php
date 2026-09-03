<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProviderProfileUpdateRequest;
use App\Http\Requests\Settings\ProviderWhatsappCredentialsUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProviderController extends Controller
{
    /**
     * Show the provider (business) settings page.
     *
     * A user may not have a provider yet — Phase 1 onboarding creates
     * one lazily on first save rather than requiring a separate
     * "create provider" step. See DOMAIN_MODEL.md §2.
     */
    public function edit(Request $request): Response
    {
        $provider = $request->user()->provider;

        return Inertia::render('settings/provider', [
            'provider' => $provider ? [
                'name' => $provider->name,
                'business_category' => $provider->business_category,
                'timezone' => $provider->timezone,
                'whatsapp_phone_number_id' => $provider->whatsapp_phone_number_id,
                'whatsapp_business_account_id' => $provider->whatsapp_business_account_id,
                'has_whatsapp_access_token' => filled($provider->whatsapp_access_token),
            ] : null,
        ]);
    }

    /**
     * Update (or create, on first save) the provider's business
     * profile. `user_id` is never taken from the request — it's always
     * the authenticated user, per SECURITY.md §7 (mass assignment).
     */
    public function updateProfile(ProviderProfileUpdateRequest $request): RedirectResponse
    {
        // The `provider()` relation already scopes by the authenticated
        // user's id (both for finding an existing row and for setting
        // the foreign key on creation) — user_id is never taken from
        // request input.
        $request->user()->provider()->updateOrCreate([], $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Business profile updated.')]);

        return to_route('provider.edit');
    }

    /**
     * Store WhatsApp Cloud API credentials for the provider.
     *
     * The access token is encrypted at rest via the Provider model's
     * `encrypted` cast (SECURITY.md §3) and is never returned to the
     * frontend in plaintext — see ProviderController::edit().
     */
    public function updateWhatsappCredentials(ProviderWhatsappCredentialsUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Don't overwrite a stored token with an empty value when the
        // field was omitted because one already exists.
        if (! array_key_exists('whatsapp_access_token', $validated)) {
            unset($validated['whatsapp_access_token']);
        }

        $request->user()->provider()->updateOrCreate([], $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('WhatsApp credentials saved.')]);

        return to_route('provider.edit');
    }
}
