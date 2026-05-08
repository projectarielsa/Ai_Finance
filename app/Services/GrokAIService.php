<?php

namespace App\Services;

use App\Models\AiLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI Service - Supports Groq API (OpenAI-compatible endpoint).
 * Uses llama-3.3-70b-versatile for text and llama-4-scout for vision.
 */
class GrokAIService
{
    protected string $apiKey;
    protected string $model;
    protected string $visionModel;
    protected string $baseUrl;
    protected string $provider;

    public function __construct(protected AppSettingService $settings)
    {
        $this->apiKey      = $settings->getAiApiKey() ?? '';
        $this->model       = $settings->getAiModel();
        $this->visionModel = $settings->getAiVisionModel();
        $this->baseUrl     = $settings->getAiBaseUrl();
        $this->provider    = $settings->getAiProvider();
    }

    /**
     * Parse a natural language transaction message.
     */
    public function parseTransaction(string $message, User $user, array $wallets, array $categories): array
    {
        $walletList   = collect($wallets)->pluck('name')->join(', ');
        $categoryList = collect($categories)->pluck('name')->join(', ');

        $systemPrompt = <<<PROMPT
Kamu adalah asisten keuangan pintar. Parse pesan berikut menjadi data transaksi keuangan dalam format JSON.

User memiliki wallet: {$walletList}
Kategori tersedia: {$categoryList}

Aturan parsing:
- Deteksi jenis transaksi: income (pemasukan), expense (pengeluaran), transfer (transfer antar wallet)
- Deteksi nominal: 25k=25000, 25rb=25000, 2jt=2000000, 1,5 juta=1500000, dll
- Deteksi wallet sumber (dari daftar wallet user jika ada)
- Deteksi wallet tujuan (hanya untuk transfer)
- Deteksi kategori (gunakan nama yang paling sesuai dari daftar kategori)
- Deteksi merchant jika ada
- "tarik tunai" dari bank = transfer ke Cash

Response HARUS dalam format JSON valid:
{
  "type": "income|expense|transfer",
  "amount": 0,
  "currency": "IDR",
  "wallet": "nama wallet sumber",
  "target_wallet": "nama wallet tujuan (hanya untuk transfer, null jika bukan transfer)",
  "category": "nama kategori",
  "description": "deskripsi singkat",
  "merchant": "nama merchant atau null",
  "confidence": 0-100,
  "error": null,
  "original_message": "pesan asli"
}

Jika tidak bisa dipahami, kembalikan: {"error": "alasan", "confidence": 0}
PROMPT;

        $startTime = microtime(true);
        try {
            $response = $this->callApi([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message],
            ], useVision: false);

            $duration = (int)((microtime(true) - $startTime) * 1000);
            $content  = $response['choices'][0]['message']['content'] ?? '{}';
            $parsed   = $this->extractJson($content);

            $this->logRequest($user->id, 'transaction_parse', $message, $content,
                $response['usage'] ?? [], $duration, true);

            return $parsed;
        } catch (\Throwable $e) {
            $duration = (int)((microtime(true) - $startTime) * 1000);
            $this->logRequest($user->id, 'transaction_parse', $message, null,
                [], $duration, false, $e->getMessage());
            Log::error('AI parseTransaction error: ' . $e->getMessage());
            return ['error' => 'AI service unavailable', 'confidence' => 0];
        }
    }

    /**
     * Scan a receipt image using vision AI (Groq Vision Model).
     */
    public function scanReceipt(string $imageBase64, string $mimeType, User $user): array
    {
        $systemPrompt = <<<PROMPT
Kamu adalah AI scanner struk belanja. Analisa gambar struk/nota berikut dan kembalikan informasi dalam format JSON.

Response format:
{
  "merchant_name": "nama toko/merchant",
  "total_amount": 0,
  "receipt_date": "YYYY-MM-DD atau null",
  "items": [{"name": "item", "qty": 1, "price": 0}],
  "category": "kategori yang sesuai (makanan/transport/belanja_harian/dll)",
  "detected_wallet": "nama wallet jika terlihat di struk (misalnya dari logo, atau null)",
  "confidence": 0-100,
  "error": null
}

Jika gambar bukan struk atau tidak terbaca, kembalikan: {"error": "alasan", "confidence": 0}
PROMPT;

        $startTime = microtime(true);
        try {
            $response = $this->callApi([
                ['role' => 'system', 'content' => $systemPrompt],
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => 'Scan struk ini dan berikan detail transaksi dalam format JSON.'],
                        ['type' => 'image_url', 'image_url' => ['url' => "data:{$mimeType};base64,{$imageBase64}"]],
                    ],
                ],
            ], useVision: true);

            $duration = (int)((microtime(true) - $startTime) * 1000);
            $content  = $response['choices'][0]['message']['content'] ?? '{}';
            $parsed   = $this->extractJson($content);

            $this->logRequest($user->id, 'receipt_scan', 'image_input', $content,
                $response['usage'] ?? [], $duration, true);

            return $parsed;
        } catch (\Throwable $e) {
            $duration = (int)((microtime(true) - $startTime) * 1000);
            $this->logRequest($user->id, 'receipt_scan', 'image_input', null,
                [], $duration, false, $e->getMessage());
            Log::error('AI scanReceipt error: ' . $e->getMessage());
            return ['error' => 'Receipt scan failed', 'confidence' => 0];
        }
    }

    /**
     * Transcribe audio to text using Groq Whisper API.
     */
    public function transcribeAudio(string $audioBase64, string $mimeType, User $user): array
    {
        $startTime = microtime(true);
        try {
            // Groq supports Whisper for audio transcription via /audio/transcriptions
            $audioContent = base64_decode($audioBase64);
            $tempFile     = tempnam(sys_get_temp_dir(), 'voice_') . '.ogg';
            file_put_contents($tempFile, $audioContent);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->timeout(60)->attach(
                'file', file_get_contents($tempFile), basename($tempFile)
            )->post("{$this->baseUrl}/audio/transcriptions", [
                'model'    => 'whisper-large-v3',
                'language' => 'id',
            ]);

            @unlink($tempFile);

            if (!$response->successful()) {
                // Fallback: Use text model to "transcribe" via vision
                return $this->transcribeViaVision($audioBase64, $mimeType, $user);
            }

            $duration      = (int)((microtime(true) - $startTime) * 1000);
            $transcription = trim($response->json('text', ''));

            $this->logRequest($user->id, 'voice_transcription', 'audio_input', $transcription,
                [], $duration, true);

            return ['transcription' => $transcription, 'success' => !empty($transcription)];
        } catch (\Throwable $e) {
            // Fallback to vision model
            return $this->transcribeViaVision($audioBase64, $mimeType, $user);
        }
    }

    /**
     * Fallback transcription via vision model (for providers that don't support Whisper).
     */
    protected function transcribeViaVision(string $audioBase64, string $mimeType, User $user): array
    {
        $startTime = microtime(true);
        try {
            $response = $this->callApi([
                ['role' => 'system', 'content' => 'Kamu adalah AI transcriber. Ubah audio berikut menjadi teks bahasa Indonesia yang akurat. Kembalikan hanya teks transkrip tanpa format tambahan.'],
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => 'Transcribe audio ini ke teks bahasa Indonesia:'],
                        ['type' => 'image_url', 'image_url' => ['url' => "data:{$mimeType};base64,{$audioBase64}"]],
                    ],
                ],
            ], useVision: true);

            $duration      = (int)((microtime(true) - $startTime) * 1000);
            $transcription = trim($response['choices'][0]['message']['content'] ?? '');

            $this->logRequest($user->id, 'voice_transcription', 'audio_input', $transcription,
                $response['usage'] ?? [], $duration, true);

            return ['transcription' => $transcription, 'success' => !empty($transcription)];
        } catch (\Throwable $e) {
            $this->logRequest($user->id, 'voice_transcription', 'audio_input', null,
                [], 0, false, $e->getMessage());
            return ['error' => $e->getMessage(), 'success' => false];
        }
    }

    /**
     * Generate AI financial insight for user.
     */
    public function generateFinancialInsight(User $user, array $stats): string
    {
        $prompt = <<<PROMPT
Berikan analisa keuangan singkat (max 3 kalimat) berdasarkan data berikut:
- Total pemasukan bulan ini: Rp {$stats['income']}
- Total pengeluaran bulan ini: Rp {$stats['expense']}
- Kategori pengeluaran terbesar: {$stats['top_category']}
- Perbandingan bulan lalu: {$stats['comparison']}

Berikan insight dalam Bahasa Indonesia yang ramah, jelas, dan actionable.
PROMPT;

        try {
            $response = $this->callApi([
                ['role' => 'system', 'content' => 'Kamu adalah analis keuangan pribadi yang ramah dan profesional.'],
                ['role' => 'user', 'content' => $prompt],
            ], useVision: false);
            return trim($response['choices'][0]['message']['content'] ?? 'Tidak ada data insight.');
        } catch (\Throwable $e) {
            Log::error('AI insight error: ' . $e->getMessage());
            return 'Insight keuangan tidak tersedia saat ini.';
        }
    }

    /**
     * Answer a financial question from WhatsApp.
     */
    public function answerFinancialQuestion(string $question, User $user, array $context): string
    {
        $contextStr = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $systemPrompt = <<<PROMPT
Kamu adalah asisten keuangan pribadi. Jawab pertanyaan user berdasarkan data keuangan berikut:
{$contextStr}

Jawab dengan singkat, jelas, dan dalam Bahasa Indonesia. Maksimal 3-4 kalimat.
Sertakan angka/nilai yang relevan.
PROMPT;

        try {
            $response = $this->callApi([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $question],
            ], useVision: false);
            $this->logRequest($user->id, 'chat', $question,
                $response['choices'][0]['message']['content'] ?? '',
                $response['usage'] ?? [], 0, true);
            return trim($response['choices'][0]['message']['content'] ?? 'Maaf, saya tidak bisa menjawab pertanyaan itu.');
        } catch (\Throwable $e) {
            Log::error('AI chat error: ' . $e->getMessage());
            return 'Maaf, layanan AI sedang tidak tersedia.';
        }
    }

    /**
     * Test API connection.
     */
    public function testConnection(): array
    {
        try {
            $response = $this->callApi([
                ['role' => 'user', 'content' => 'Hello, respond with just "OK"'],
            ], useVision: false);
            return ['success' => true, 'message' => 'Connection successful! Provider: ' . $this->provider . ', Model: ' . $this->model];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Call the AI API (Groq-compatible OpenAI format).
     */
    protected function callApi(array $messages, bool $useVision = false): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('AI API key not configured. Set via Admin Panel → API Credentials.');
        }

        $model = $useVision ? $this->visionModel : $this->model;

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type'  => 'application/json',
        ])->timeout(60)->post("{$this->baseUrl}/chat/completions", [
            'model'       => $model,
            'messages'    => $messages,
            'temperature' => 0.1,
            'max_tokens'  => 1024,
        ]);

        if (!$response->successful()) {
            $errorBody = $response->json();
            $errorMsg  = $errorBody['error']['message'] ?? $response->body();
            throw new \RuntimeException("AI API error ({$response->status()}): {$errorMsg}");
        }

        return $response->json();
    }

    protected function extractJson(string $content): array
    {
        // Try to extract JSON from markdown code blocks
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $content, $matches)) {
            $content = $matches[1];
        }
        // Try to find JSON object
        if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        return ['error' => 'Could not parse AI response', 'confidence' => 0, 'raw' => $content];
    }

    protected function logRequest(int $userId, string $type, string $prompt, ?string $response,
        array $usage, int $duration, bool $success, ?string $error = null): void
    {
        AiLog::create([
            'user_id'           => $userId,
            'provider'          => $this->provider,
            'model'             => $this->model,
            'type'              => $type,
            'prompt'            => substr($prompt, 0, 2000),
            'response'          => $response ? substr($response, 0, 4000) : null,
            'prompt_tokens'     => $usage['prompt_tokens'] ?? 0,
            'completion_tokens' => $usage['completion_tokens'] ?? 0,
            'total_tokens'      => $usage['total_tokens'] ?? 0,
            'duration_ms'       => $duration,
            'success'           => $success,
            'error_message'     => $error,
        ]);
    }
}
