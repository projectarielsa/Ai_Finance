<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTelegramMessage;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    public function __construct(protected TelegramBotService $telegram) {}

    /**
     * Receive updates from Telegram webhook.
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();
        Log::channel('daily')->info('Telegram Webhook received', ['update_id' => $payload['update_id'] ?? null]);

        // Validate Telegram secret token header
        // Reject if: secret is configured AND (header is missing OR header doesn't match)
        $expectedSecret = config('services.telegram.webhook_secret');
        if ($expectedSecret) {
            $provided = $request->header('X-Telegram-Bot-Api-Secret-Token');
            if ($provided !== $expectedSecret) {
                Log::warning('Telegram Webhook: invalid or missing secret token');
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        // Process synchronously — no queue worker needed
        try {
            $webhookService = app(\App\Services\TelegramWebhookService::class);
            $webhookService->process($payload);
        } catch (\Throwable $e) {
            Log::error('Telegram Webhook processing error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return response()->json(['ok' => true], 200);
    }

    /**
     * Set webhook URL on Telegram's server.
     * Call once via: GET /telegram/setup-webhook
     */
    public function setupWebhook(Request $request)
    {
        $url    = route('webhook.telegram');
        $secret = config('services.telegram.webhook_secret');
        $result = $this->telegram->setWebhook($url, $secret ?: null);

        if ($result['ok'] ?? false) {
            return response()->json(['success' => true, 'message' => 'Webhook set successfully', 'url' => $url, 'telegram' => $result]);
        }

        return response()->json(['success' => false, 'message' => $result['description'] ?? 'Failed to set webhook', 'result' => $result], 422);
    }

    /**
     * Remove webhook (switch to polling mode).
     */
    public function deleteWebhook()
    {
        $result = $this->telegram->deleteWebhook();
        return response()->json($result);
    }

    /**
     * Get current webhook info.
     */
    public function webhookInfo()
    {
        $info = $this->telegram->getWebhookInfo();
        return response()->json($info);
    }

    /**
     * Test bot connection (getMe).
     */
    public function testConnection()
    {
        $result = $this->telegram->getMe();
        return response()->json($result);
    }
}
