<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Laravel\Cashier\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Support\Facades\Route;

//Route::get('/', function () {
//    return view('welcome');
//});

//Route::post('stripe/webhook', '\Laravel\Cashier\Http\Controllers\WebhookController@handleWebhook')
//    ->withoutMiddleware(VerifyCsrfToken::class);
