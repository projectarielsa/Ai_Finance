<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TelegramMessage;
use App\Services\AppSettingService;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;

class TelegramSettingController extends Controller
{
    public function __construct(
        protected AppSettingService $settings,
        protected TelegramBotService $telegram
    ) {}

    public function index()
    {
        $webhookInfo  = $this->telegram->getWebhookInfo();
        $botInfo      = null;
        $recentMsgs   = TelegramMessage::with('user')->latest()->limit(20)->get();
        $totalMsgs    = TelegramMessage::count();
        $uniqueUsers  = TelegramMessage::where('direction', 'inbound')->distinct('telegram_user_id')->count();

        return view('admin.telegram.index', compact(
            'webhookInfo', 'botInfo', 'recentMsgs', 'totalMsgs', 'uniqueUsers'
        ));
    }

    public function testConnection()
    {
        $result = $this->telegram->getMe();
        return response()->json($result);
    }

    public function setWebhook(Request $request)
    {
        $url    = route('webhook.telegram');
        $secret = config('services.telegram.webhook_secret');
        $result = $this->telegram->setWebhook($url, $secret ?: null);

        if ($result['ok'] ?? false) {
            return response()->json(['success' => true, 'message' => "✅ Webhook berhasil diset!\nURL: {$url}"]);
        }

        return response()->json(['success' => false, 'message' => '❌ Gagal: ' . ($result['description'] ?? 'Unknown error')]);
    }

    public function deleteWebhook()
    {
        $result = $this->telegram->deleteWebhook();
        return response()->json([
            'success' => $result['ok'] ?? false,
            'message' => ($result['ok'] ?? false) ? 'Webhook dihapus.' : 'Gagal menghapus webhook.',
        ]);
    }

    public function sendTest(Request $request)
    {
        $request->validate([
            'chat_id' => 'required|string',
            'message' => 'required|string',
        ]);

        $success = $this->telegram->sendMessage($request->chat_id, $request->message);
        return response()->json([
            'success' => $success,
            'message' => $success ? '✅ Pesan terkirim!' : '❌ Gagal mengirim pesan.',
        ]);
    }
}
