<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Webhook alias (for gateways that POST to /api/webhook/whatsapp)
Route::post('/webhook/whatsapp', [App\Http\Controllers\WebhookController::class, 'whatsapp']);
