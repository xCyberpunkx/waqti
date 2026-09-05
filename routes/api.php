<?php

use App\Http\Controllers\Webhooks\WhatsappWebhookController;
use App\Http\Middleware\VerifyWhatsappWebhookSignature;
use Illuminate\Support\Facades\Route;

// No auth/session/CSRF here by design — this is Meta calling us, not a
// browser. See SECURITY.md §4 and §8.
Route::get('whatsapp/webhook', [WhatsappWebhookController::class, 'verify'])
    ->name('whatsapp.webhook.verify');

Route::post('whatsapp/webhook', [WhatsappWebhookController::class, 'handle'])
    ->middleware([VerifyWhatsappWebhookSignature::class, 'throttle:whatsapp-webhook'])
    ->name('whatsapp.webhook.handle');
