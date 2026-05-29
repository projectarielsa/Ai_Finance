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
        $today        = now()->format('Y-m-d (l, d F Y)');

        $systemPrompt = <<<PROMPT
Kamu adalah asisten keuangan pintar. Parse pesan berikut menjadi data transaksi keuangan dalam format JSON.

Hari ini: {$today}

User memiliki wallet: {$walletList}
Kategori tersedia: {$categoryList}

PENTING — Bedakan antara TRANSAKSI vs BUKAN TRANSAKSI:
- TRANSAKSI: pesan yang MEMERINTAHKAN untuk mencatat uang masuk/keluar/transfer
  Contoh transaksi: "beli kopi 25rb gopay", "gaji masuk 5jt bca", "transfer 100rb ke dana"
- BUKAN TRANSAKSI: pertanyaan, cek saldo, obrolan, atau pesan ambigu
  Contoh BUKAN transaksi:
  • "gopay gue ada 5k gak?" (ini PERTANYAAN, bukan perintah catat)
  • "berapa saldo bca?" (pertanyaan saldo)
  • "bulan ini habis berapa?" (pertanyaan rekap)
  • "50rb cukup gak ya?" (pertanyaan/opini)
  • "harga kopi 25rb mahal gak?" (pertanyaan, bukan pembelian)
  • "ada uang 100rb di dompet" (pernyataan, bukan transaksi baru)

Jika pesan adalah PERTANYAAN atau BUKAN perintah catat transaksi, WAJIB kembalikan:
{"error": "Ini pertanyaan, bukan transaksi", "confidence": 0}

Aturan parsing (hanya jika memang transaksi):
- Deteksi jenis transaksi: income (pemasukan), expense (pengeluaran), transfer (transfer antar wallet)
- Deteksi nominal: 25k=25000, 25rb=25000, 2jt=2000000, 1,5 juta=1500000, dll
- Deteksi wallet sumber (dari daftar wallet user jika ada)
- Deteksi wallet tujuan (hanya untuk transfer)
- Deteksi kategori (gunakan nama yang paling sesuai dari daftar kategori)
- Deteksi merchant jika ada
- "tarik tunai" dari bank = transfer ke Cash
- Deteksi TANGGAL & WAKTU jika disebutkan dalam pesan:
  • "tanggal 26 mei 2026" → "2026-05-26"
  • "kemarin" → tanggal kemarin dari hari ini
  • "tadi pagi", "tadi malam" → hari ini
  • "26 mei jam 18.00" → date: "2026-05-26", time: "18:00"
  • "minggu lalu" → tanggal 7 hari yang lalu
  • Jika TIDAK ada tanggal disebutkan → isi null (artinya hari ini)

Pedoman confidence:
- 90-100: pesan jelas sekali adalah transaksi (ada kata kerja + nominal + wallet)
- 70-89: kemungkinan besar transaksi tapi kurang lengkap
- 40-69: ambigu, bisa transaksi bisa bukan — berikan confidence rendah
- 0-39: kemungkinan besar BUKAN transaksi — kembalikan error

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
  "transaction_date": "YYYY-MM-DD atau null (null = hari ini)",
  "transaction_time": "HH:MM format 24 jam atau null",
  "confidence": 0-100,
  "error": null,
  "original_message": "pesan asli"
}

Jika BUKAN transaksi atau tidak bisa dipahami, kembalikan: {"error": "alasan", "confidence": 0}
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

PENTING: Perhatikan jam/waktu transaksi yang tertera di struk. Biasanya ada di dekat tanggal atau di bagian header/footer struk.

Response format:
{
  "merchant_name": "nama toko/merchant",
  "total_amount": 0,
  "receipt_date": "YYYY-MM-DD atau null",
  "receipt_time": "HH:MM atau null (format 24 jam, contoh: 14:30, 09:15)",
  "items": [{"name": "item", "qty": 1, "price": 0}],
  "category": "kategori yang sesuai (makanan/transport/belanja_harian/dll)",
  "detected_wallet": "nama wallet jika terlihat di struk (misalnya dari logo, atau null)",
  "confidence": 0-100,
  "error": null
}

Catatan:
- receipt_date: tanggal transaksi dalam format YYYY-MM-DD
- receipt_time: jam/waktu transaksi dalam format HH:MM (24 jam). Cari di struk biasanya tertulis seperti "14:30", "09:15:22", "Jam: 10.30", "Time: 14:30", dll. Jika tidak ada waktu yang terlihat, isi null.

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
                $response['usage'] ?? [], $duration, true, null, $this->visionModel);

            return $parsed;
        } catch (\Throwable $e) {
            $duration = (int)((microtime(true) - $startTime) * 1000);
            $this->logRequest($user->id, 'receipt_scan', 'image_input', null,
                [], $duration, false, $e->getMessage(), $this->visionModel);
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
        $tempFile  = null; // inisialisasi di sini agar selalu terdefinisi di catch block
        try {
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
                $duration = (int)((microtime(true) - $startTime) * 1000);
                $this->logRequest($user->id, 'voice_transcription', 'audio_input', null,
                    [], $duration, false, 'Whisper API error: ' . $response->body(), 'whisper-large-v3');
                return ['error' => 'Transkripsi audio gagal. Silakan kirim pesan teks.', 'success' => false];
            }

            $duration      = (int)((microtime(true) - $startTime) * 1000);
            $transcription = trim($response->json('text', ''));

            $this->logRequest($user->id, 'voice_transcription', 'audio_input', $transcription,
                [], $duration, true, null, 'whisper-large-v3');

            return ['transcription' => $transcription, 'success' => !empty($transcription)];
        } catch (\Throwable $e) {
            $duration = (int)((microtime(true) - $startTime) * 1000);
            @unlink($tempFile ?? '');
            $this->logRequest($user->id, 'voice_transcription', 'audio_input', null,
                [], $duration, false, $e->getMessage(), 'whisper-large-v3');
            Log::error('AI transcribeAudio error: ' . $e->getMessage());
            return ['error' => 'Transkripsi audio gagal. Silakan kirim pesan teks.', 'success' => false];
        }
    }
/**
 * Generate AI financial insight for user.
 */
public function generateFinancialInsight(User $user, array $stats): string
{
    $income       = $stats['income'] ?? 0;
    $expense      = $stats['expense'] ?? 0;
    $topCategory  = $stats['top_category'] ?? 'lainnya';
    $comparison   = $stats['comparison'] ?? 'stabil';
    $savingRate   = $stats['saving_rate'] ?? 0;
    $healthScore  = $stats['health_score'] ?? 0;
    $prediction   = $stats['prediction'] ?? 0;
$prompt = <<<PROMPT
Kamu adalah AI financial coach modern seperti Cleo, Copilot Money, atau Notion AI finance assistant.

Cara bicara:
- santai
- modern
- singkat
- natural
- sedikit casual
- seperti ngobrol
- bukan artikel
- bukan customer service bank

JANGAN gunakan:
- "perlu diperhatikan"
- "disarankan"
- "sebaiknya"
- "Anda"
- "berdasarkan data"
- "tampaknya"

Fokus:
- insight paling penting
- cashflow
- kebiasaan spending
- kondisi saldo
- saving rate
- dan kasih saran kepada pengguna harus apa dan bagaimana

Data user:
Income: Rp {$income}
Expense: Rp {$expense}
Top Category: {$topCategory}
Comparison: {$comparison}
Saving Rate: {$savingRate}%
Health Score: {$healthScore}
Predicted Balance: Rp {$prediction}

Contoh gaya yang benar:

"Tagihan bulan ini lumayan makan cashflow, jadi saving rate masih belum gerak banyak. Untungnya kondisi saldo masih aman buat nutup kebutuhan sampai akhir bulan."

atau

"Pengeluaran bulan ini mulai agresif dibanding biasanya, terutama di makanan dan tagihan. Kalau ritmenya tetap sama, saldo akhir bulan bisa kepotong lumayan cepat."

atau

"Cashflow lagi bagus bulan ini. Pemasukan masih jauh lebih tinggi dibanding pengeluaran dan kondisi finansial tetap stabil."

WAJIB:
- maksimal 10 kalimat
- jangan formal
- jangan panjang
- jangan terdengar seperti ChatGPT
- jangan menjelaskan semua data
- cukup insight inti saja
PROMPT;

    try {

        $response = $this->callApi([
            [
                'role' => 'system',
                'content' => 'Kamu adalah AI financial advisor modern yang natural dan premium.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ],
        ], useVision: false);

        return trim(
            $response['choices'][0]['message']['content']
            ?? 'Cashflow bulan ini masih cukup stabil.'
        );

    } catch (\Throwable $e) {

        \Log::error('AI insight error: ' . $e->getMessage());

        return 'Cashflow bulan ini masih cukup sehat dan pengeluaran tetap terkendali.';
    }
}


    /**
     * Answer a financial question from Telegram using full DB context.
     */
    public function answerFinancialQuestion(string $question, User $user, array $context): string
    {
        $contextJson  = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $name         = $user->name;

        $systemPrompt = <<<PROMPT
Kamu adalah asisten keuangan pribadi AI yang cerdas dan ramah untuk {$name}.

DATA KEUANGAN USER (real-time dari database):
{$contextJson}

TUGAS:
- Jawab pertanyaan user berdasarkan data di atas secara akurat dan spesifik
- Jika user minta "rekap", "rangkuman", "laporan bulanan", "buatkan laporan" → buat laporan lengkap dan terstruktur dengan semua angka
- Jika user minta analisa → berikan insight yang actionable dan personal
- Gunakan format Markdown Telegram: *bold*, _italic_, `code`
- Gunakan emoji yang relevan agar mudah dibaca
- Jawab dalam Bahasa Indonesia yang natural dan ramah
- Selalu sertakan angka spesifik dari data, JANGAN generik
- Jika diminta rekap/laporan bulanan: tampilkan pemasukan, pengeluaran, cashflow, top kategori, saldo wallet
PROMPT;

        try {
            $response = $this->callApi([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $question],
            ], useVision: false);

            $answer = trim($response['choices'][0]['message']['content'] ?? 'Maaf, saya tidak bisa menjawab pertanyaan itu.');

            $this->logRequest($user->id, 'chat', $question, $answer,
                $response['usage'] ?? [], 0, true);

            return $answer;
        } catch (\Throwable $e) {
            Log::error('AI chat error: ' . $e->getMessage());
            return 'Maaf, layanan AI sedang tidak tersedia.';
        }
    }

    /**
     * Simple chat method — send a prompt and get a text response.
     */
    public function chat(string $prompt): string
    {
        try {
            $response = $this->callApi([
                ['role' => 'user', 'content' => $prompt],
            ], useVision: false);

            return trim($response['choices'][0]['message']['content'] ?? '');
        } catch (\Throwable $e) {
            Log::error('AI chat (simple) error: ' . $e->getMessage());
            return '';
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
            'temperature' => 0.9,
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
        array $usage, int $duration, bool $success, ?string $error = null, ?string $modelOverride = null): void
    {
        AiLog::create([
            'user_id'           => $userId,
            'provider'          => $this->provider,
            'model'             => $modelOverride ?? $this->model,
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
