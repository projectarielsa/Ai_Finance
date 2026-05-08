<?php

namespace App\Jobs;

use App\Services\WhatsAppWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(protected array $payload) {}

    public function handle(WhatsAppWebhookService $webhookService): void
    {
        $webhookService->process($this->payload);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessWhatsAppMessage job failed: ' . $exception->getMessage(), [
            'payload' => $this->payload,
        ]);
    }
}
