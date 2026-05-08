<?php

namespace App\Services;

use App\Models\User;
use App\Models\VoiceNoteTranscription;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class VoiceNoteTranscriptionService
{
    public function __construct(
        protected GrokAIService $grokAI,
        protected TransactionParserService $transactionParser
    ) {}

    /**
     * Process a voice note from WhatsApp.
     */
    public function processVoiceNote(string $audioPath, User $user, ?int $whatsappMessageId = null): array
    {
        $transcriptionRecord = VoiceNoteTranscription::create([
            'user_id'    => $user->id,
            'message_id' => $whatsappMessageId, // works for both Telegram & WhatsApp
            'audio_path' => $audioPath,
            'audio_format'         => pathinfo($audioPath, PATHINFO_EXTENSION),
            'status'               => 'pending',
        ]);

        // Read audio and encode to base64
        $audioContent = Storage::disk('public')->get($audioPath);
        if (!$audioContent) {
            $transcriptionRecord->update(['status' => 'failed', 'error_message' => 'Audio file not found']);
            return ['success' => false, 'message' => 'File audio tidak ditemukan'];
        }

        $mimeType    = $this->detectMimeType($audioPath);
        $audioBase64 = base64_encode($audioContent);

        // Transcribe
        $result = $this->grokAI->transcribeAudio($audioBase64, $mimeType, $user);

        if (!$result['success'] || empty($result['transcription'])) {
            $transcriptionRecord->update(['status' => 'failed', 'error_message' => $result['error'] ?? 'Transcription failed']);
            return ['success' => false, 'message' => 'Voice note tidak dapat ditranskrip. Pastikan suara jelas.'];
        }

        $transcription = $result['transcription'];
        $transcriptionRecord->update([
            'transcription'              => $transcription,
            'transcription_provider'     => 'grok',
            'status'                     => 'transcribed',
        ]);

        // Parse the transcription as a transaction
        $parseResult = $this->transactionParser->parseAndSave($transcription, $user);

        if ($parseResult['success']) {
            $transcriptionRecord->update([
                'transaction_id' => $parseResult['transaction']->id,
                'status'         => 'parsed',
            ]);
            return [
                'success'       => true,
                'transcription' => $transcription,
                'transaction'   => $parseResult['transaction'],
                'message'       => "🎤 Voice note ditranskrip:\n\"{$transcription}\"\n\n" . $parseResult['message'],
            ];
        }

        return [
            'success'       => false,
            'transcription' => $transcription,
            'message'       => "🎤 Transkrip voice note:\n\"{$transcription}\"\n\n" . ($parseResult['message'] ?? 'Tidak dapat diproses sebagai transaksi.'),
        ];
    }

    protected function detectMimeType(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match($ext) {
            'ogg'  => 'audio/ogg',
            'mp3'  => 'audio/mpeg',
            'wav'  => 'audio/wav',
            'mp4'  => 'audio/mp4',
            'm4a'  => 'audio/mp4',
            default => 'audio/ogg',
        };
    }
}
