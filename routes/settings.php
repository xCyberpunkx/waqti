<?php

use App\Http\Controllers\Settings\AvailabilityController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\ProviderController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('settings/provider', [ProviderController::class, 'edit'])->name('provider.edit');
    Route::patch('settings/provider', [ProviderController::class, 'updateProfile'])->name('provider.update');
    Route::patch('settings/provider/whatsapp', [ProviderController::class, 'updateWhatsappCredentials'])
        ->name('provider.whatsapp.update');

    Route::get('settings/availability', [AvailabilityController::class, 'edit'])->name('availability.edit');
    Route::post('settings/availability/rules', [AvailabilityController::class, 'upsertRule'])
        ->name('availability.rules.upsert');
    Route::delete('settings/availability/rules/{rule}', [AvailabilityController::class, 'destroyRule'])
        ->name('availability.rules.destroy');
    Route::post('settings/availability/exceptions', [AvailabilityController::class, 'storeException'])
        ->name('availability.exceptions.store');
    Route::delete('settings/availability/exceptions/{exception}', [AvailabilityController::class, 'destroyException'])
        ->name('availability.exceptions.destroy');
    Route::get('settings/availability/slots', [AvailabilityController::class, 'slots'])
        ->name('availability.slots');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
