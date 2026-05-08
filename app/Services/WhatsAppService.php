<?php

namespace App\Services;

use App\Models\WhatsappGateway;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected ?WhatsappGateway $gateway;

    public function __construct(protected AppSettingService $settings)
    {
        $this->gateway = $settings->getWhatsappGateway();
    }

    /**
     * Send a text message via WhatsApp.
     */
    public function sendMessage(string $to, string $message): bool
    {
        if (!$this->gateway) {
            Log::warning('WhatsApp gateway not configured');
            return false;
        }

        try {
            $payload  = $this->buildSendPayload($to, $message);
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(15)
                ->post($this->gateway->base_url . '/send-message', $payload);

            if ($response->successful()) {
                $this->logOutbound($to, $message, $response->json());
                return true;
            }

            Log::error('WhatsApp send failed: ' . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsApp sendMessage error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Download media file from WhatsApp.
     */
    public function downloadMedia(string $mediaUrl, string $filename): ?string
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(30)
                ->get($mediaUrl);

            if ($response->successful()) {
                $path = 'whatsapp-media/' . date('Y/m') . '/' . $filename;
                \Storage::disk('public')->put($path, $response->body());
                return $path;
            }
            return null;
        } catch (\Throwable $e) {
            Log::error('WhatsApp downloadMedia error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Test connection to gateway.
     */
    public function testConnection(?WhatsappGateway $gateway = null): array
    {
        $gw = $gateway ?? $this->gateway;
        if (!$gw) {
            return ['success' => false, 'message' => 'No gateway configured'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $gw->api_key,
                'Content-Type'  => 'application/json',
            ])->timeout(10)->get($gw->base_url . '/status');

            if ($response->successful()) {
                $gw->update(['status' => 'connected', 'last_connected_at' => now()]);
                return ['success' => true, 'message' => 'Gateway connected', 'data' => $response->json()];
            }
            $gw->update(['status' => 'disconnected']);
            return ['success' => false, 'message' => 'Gateway returned: ' . $response->status()];
        } catch (\Throwable $e) {
            $gw->update(['status' => 'disconnected']);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send a test message from admin panel.
     */
    public function sendTestMessage(string $to, string $message, WhatsappGateway $gateway): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $gateway->api_key,
                'Content-Type'  => 'application/json',
            ])->timeout(15)->post($gateway->base_url . '/send-message', [
                'phone'   => $to,
                'message' => $message,
                'device'  => $gateway->device_id,
            ]);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('WhatsApp testMessage error: ' . $e->getMessage());
            return false;
        }
    }

    protected function buildSendPayload(string $to, string $message): array
    {
        return [
            'phone'   => $to,
            'message' => $message,
            'device'  => $this->gateway?->device_id,
        ];
    }

    protected function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . ($this->gateway?->api_key ?? ''),
            'Content-Type'  => 'application/json',
        ];
    }

    protected function logOutbound(string $to, string $message, array $response): void
    {
        WhatsappMessage::create([
            'sender_phone'  => $this->gateway?->sender_number ?? '',
            'receiver_phone' => $to,
            'direction'     => 'outbound',
            'type'          => 'text',
            'content'       => $message,
            'status'        => 'sent',
            'raw_payload'   => $response,
            'sent_at'       => now(),
        ]);
    }
}
