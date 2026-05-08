<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessWhatsAppMessage;
use App\Models\WhatsappGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function whatsapp(Request $request)
    {
        $payload = $request->all();
        Log::channel('daily')->info('WhatsApp Webhook received', ['payload' => $payload]);

        // Validate webhook secret
        $gateway = WhatsappGateway::where('is_default', true)->where('is_active', true)->first()
                ?? WhatsappGateway::where('is_active', true)->first();

        if ($gateway && $gateway->webhook_secret) {
            $secret = $request->header('X-Webhook-Secret')
                   ?? $request->header('Authorization')
                   ?? $request->input('secret');

            if ($secret !== $gateway->webhook_secret && str_replace('Bearer ', '', $secret) !== $gateway->webhook_secret) {
                Log::warning('WhatsApp Webhook: invalid secret');
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        // Dispatch to queue for async processing
        ProcessWhatsAppMessage::dispatch($payload)->onQueue('whatsapp');

        return response()->json(['status' => 'queued'], 200);
    }

    /**
     * Test endpoint to verify webhook is reachable.
     */
    public function verify(Request $request)
    {
        $challenge = $request->input('hub_challenge') ?? $request->input('challenge');
        if ($challenge) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }
        return response()->json(['status' => 'ok', 'message' => 'Webhook endpoint active']);
    }
}
