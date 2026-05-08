<?php

namespace App\Http\Middleware;

use App\Models\WhatsappGateway;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $gateway = WhatsappGateway::where('is_default', true)->where('is_active', true)->first();

        if ($gateway && $gateway->webhook_secret) {
            $provided = $request->header('X-Webhook-Secret')
                     ?? $request->bearerToken()
                     ?? $request->input('secret');

            if ($provided !== $gateway->webhook_secret) {
                return response()->json(['error' => 'Invalid webhook signature'], 401);
            }
        }

        return $next($request);
    }
}
