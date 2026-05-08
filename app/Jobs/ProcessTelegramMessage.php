<?php

namespace App\Jobs;

use App\Services\TelegramWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTelegramMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(protected array $payload) {}

    public function handle(TelegramWebhookService $webhookService): void
    {
        $webhookService->process($this->payload);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessTelegramMessage job failed: ' . $exception->getMessage(), [
            'update_id' => $this->payload['update_id'] ?? null,
        ]);
    }
}
