<?php

namespace App\Services;

use App\Models\TelegramMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TelegramBotService
{
    protected string $token;
    protected string $baseUrl;

    public function __construct(protected AppSettingService $settings)
    {
        $this->token   = $settings->getTelegramToken() ?? '';
        $this->baseUrl = "https://api.telegram.org/bot{$this->token}";
    }

    /**
     * Send a text message to a chat.
     */
    public function sendMessage(int|string $chatId, string $text, array $extra = []): bool
    {
        if (empty($this->token)) {
            Log::warning('Telegram bot token not configured');
            return false;
        }

        try {
            $response = Http::timeout(15)->post("{$this->baseUrl}/sendMessage", array_merge([
                'chat_id'    => $chatId,
                'text'       => $text,
                'parse_mode' => 'Markdown',
            ], $extra));

            if ($response->successful()) {
                $this->logOutbound($chatId, $text, $response->json());
                return true;
            }

            // Markdown parsing failed → retry without parse_mode (plain text)
            $errorBody = $response->json();
            $errorDesc = $errorBody['description'] ?? '';
            if (str_contains($errorDesc, "can't parse") || str_contains($errorDesc, 'parse entities')) {
                Log::warning("Telegram Markdown parse error, retrying as plain text: {$errorDesc}");
                $plainExtra = $extra;
                unset($plainExtra['parse_mode']);
                $retry = Http::timeout(15)->post("{$this->baseUrl}/sendMessage", array_merge([
                    'chat_id' => $chatId,
                    'text'    => $this->stripMarkdown($text),
                ], $plainExtra));
                if ($retry->successful()) {
                    $this->logOutbound($chatId, $text, $retry->json());
                    return true;
                }
                Log::error('Telegram sendMessage plain retry also failed: ' . $retry->body());
            } else {
                Log::error('Telegram sendMessage failed: ' . $response->body());
            }

            return false;
        } catch (\Throwable $e) {
            Log::error('Telegram sendMessage error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Download a file from Telegram servers.
     * Returns local storage path or null on failure.
     */
    public function downloadFile(string $fileId, string $filename): ?string
    {
        try {
            // Step 1: Get file path from Telegram
            $infoRes = Http::timeout(10)->get("{$this->baseUrl}/getFile", ['file_id' => $fileId]);
            if (!$infoRes->successful()) return null;

            $filePath = $infoRes->json('result.file_path');
            if (!$filePath) return null;

            // Step 2: Download the file content
            $downloadUrl = "https://api.telegram.org/file/bot{$this->token}/{$filePath}";
            $fileRes     = Http::timeout(60)->get($downloadUrl);
            if (!$fileRes->successful()) return null;

            // Step 3: Save to local storage
            $ext  = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'jpg';
            $path = 'telegram-media/' . date('Y/m') . '/' . $filename . '.' . $ext;

            // Simpan file
            Storage::disk('public')->put($path, $fileRes->body());

            // Verifikasi file tersimpan
            $saved = Storage::disk('public')->exists($path);
            if (!$saved) {
                // Fallback: simpan langsung via file_put_contents
                $absolutePath = storage_path('app/public/' . $path);
                @mkdir(dirname($absolutePath), 0775, true);
                file_put_contents($absolutePath, $fileRes->body());
                @chmod($absolutePath, 0644);
            }

            Log::info('Telegram downloadFile: saved', ['path' => $path, 'size' => strlen($fileRes->body())]);
            return $path;
        } catch (\Throwable $e) {
            Log::error('Telegram downloadFile error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Set webhook URL so Telegram pushes updates to our server.
     */
    public function setWebhook(string $url, ?string $secretToken = null): array
    {
        $payload = ['url' => $url];
        if ($secretToken) {
            $payload['secret_token'] = $secretToken;
        }

        try {
            $response = Http::timeout(15)->post("{$this->baseUrl}/setWebhook", $payload);
            return $response->json() ?? ['ok' => false];
        } catch (\Throwable $e) {
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /**
     * Delete (remove) webhook — fall back to polling.
     */
    public function deleteWebhook(): array
    {
        try {
            $response = Http::timeout(15)->post("{$this->baseUrl}/deleteWebhook");
            return $response->json() ?? ['ok' => false];
        } catch (\Throwable $e) {
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /**
     * Get bot info (for connection test).
     */
    public function getMe(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/getMe");
            if ($response->successful() && $response->json('ok')) {
                return ['success' => true, 'data' => $response->json('result'), 'message' => 'Bot connected: @' . $response->json('result.username')];
            }
            return ['success' => false, 'message' => $response->json('description') ?? 'Unknown error'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get webhook info.
     */
    public function getWebhookInfo(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/getWebhookInfo");
            return $response->json('result') ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Answer a callback query — removes loading spinner on inline buttons.
     */
    public function answerCallbackQuery(string $callbackQueryId, string $text = ''): void
    {
        try {
            Http::timeout(5)->post("{$this->baseUrl}/answerCallbackQuery", [
                'callback_query_id' => $callbackQueryId,
                'text'              => $text,
            ]);
        } catch (\Throwable $e) {
            Log::error('Telegram answerCallbackQuery error: ' . $e->getMessage());
        }
    }

    /**
     * Edit an existing message text (e.g. to remove inline keyboard after selection).
     */
    public function editMessageText(int|string $chatId, int $messageId, string $text): bool
    {
        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/editMessageText", [
                'chat_id'      => $chatId,
                'message_id'   => $messageId,
                'text'         => $text,
                'parse_mode'   => 'Markdown',
                'reply_markup' => json_encode(['inline_keyboard' => []]), // Remove keyboard
            ]);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Telegram editMessageText error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a formatted financial report message.
     */
    public function sendReport(int|string $chatId, string $reportText): bool
    {
        return $this->sendMessage($chatId, $reportText);
    }

    protected function logOutbound(int|string $chatId, string $text, array $response): void
    {
        TelegramMessage::create([
            'chat_id'   => (string) $chatId,
            'direction' => 'outbound',
            'type'      => 'text',
            'content'   => $text,
            'status'    => 'sent',
            'raw_payload' => $response,
            'sent_at'   => now(),
        ]);
    }

    /**
     * Strip Markdown formatting for plain text fallback.
     */
    protected function stripMarkdown(string $text): string
    {
        // Remove bold/italic markers
        $text = preg_replace('/\*+(.*?)\*+/', '$1', $text);
        $text = preg_replace('/_(.*?)_/', '$1', $text);
        $text = preg_replace('/`(.*?)`/', '$1', $text);
        // Remove inline links [text](url)
        $text = preg_replace('/\[(.*?)\]\(.*?\)/', '$1', $text);
        return $text;
    }
}
