<?php

use App\Http\Controllers\DonateController;
use App\Http\Controllers\DonationHistoryController;
use App\Http\Controllers\IyzicoWebhookController;
use App\Http\Controllers\MagicLinkController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::post('/donate/start', [DonateController::class, 'start'])
    ->middleware('throttle:'.env('RATE_LIMIT_DONATE', 10).',1')
    ->name('donate.start');

Route::post('/donate/callback', [DonateController::class, 'callback'])->name('donate.callback');

Route::post('/webhooks/iyzico', [IyzicoWebhookController::class, 'handle']);

Route::post('/magic-links/request', [MagicLinkController::class, 'requestLink'])
    ->middleware('throttle:5,10')
    ->name('magic.request');

Route::get('/donations', [DonationHistoryController::class, 'index'])->name('donations.index');
