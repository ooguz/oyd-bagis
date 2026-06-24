<?php

use App\Http\Controllers\DonateController;
use App\Http\Controllers\DonationHistoryController;
use App\Http\Controllers\IyzicoWebhookController;
use App\Http\Controllers\MagicLinkController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('/kvkk-ve-gizlilik', 'policy')->name('policy');

Route::post('/donate/start', [DonateController::class, 'start'])
    ->middleware('throttle:'.env('RATE_LIMIT_DONATE', 10).',1')
    ->name('donate.start');

Route::match(['GET', 'POST'], '/donate/callback', [DonateController::class, 'callback'])->name('donate.callback');

// Monthly subscription callback
Route::match(['GET', 'POST'], '/donate/subscription/callback', [DonateController::class, 'subscriptionCallback'])
    ->name('donate.subscription.callback');

Route::post('/webhooks/iyzico', [IyzicoWebhookController::class, 'handle']);

Route::post('/magic-links/request', [MagicLinkController::class, 'requestLink'])
    ->middleware('throttle:5,10')
    ->name('magic.request');

Route::get('/donations', [DonationHistoryController::class, 'index'])->name('donations.index');

// Subscription management routes (protected by magic link token in request body)
Route::post('/donations/subscription/{subscription}/cancel', [DonationHistoryController::class, 'cancelSubscription'])
    ->name('donations.subscription.cancel');

Route::post('/donations/subscription/{subscription}/card-update', [DonationHistoryController::class, 'cardUpdateInit'])
    ->name('donations.subscription.card-update');

Route::match(['GET', 'POST'], '/donations/card-update/callback', [DonationHistoryController::class, 'cardUpdateCallback'])
    ->name('donations.card-update.callback');
