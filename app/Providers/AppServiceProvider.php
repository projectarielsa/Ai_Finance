<?php

namespace App\Providers;

use App\Services\AppSettingService;
use App\Services\GrokAIService;
use App\Services\ReceiptScannerService;
use App\Services\TelegramBotService;
use App\Services\TelegramWebhookService;
use App\Services\TransactionParserService;
use App\Services\VoiceNoteTranscriptionService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AppSettingService::class);
        $this->app->singleton(GrokAIService::class);
<<<<<<< Updated upstream
=======
        $this->app->singleton(WhatsAppService::class);
>>>>>>> Stashed changes
        $this->app->singleton(TelegramBotService::class);

        $this->app->bind(TransactionParserService::class, function ($app) {
            return new TransactionParserService(
                $app->make(GrokAIService::class)
            );
        });

        $this->app->bind(ReceiptScannerService::class, function ($app) {
            return new ReceiptScannerService(
                $app->make(GrokAIService::class)
            );
        });

        $this->app->bind(VoiceNoteTranscriptionService::class, function ($app) {
            return new VoiceNoteTranscriptionService(
                $app->make(GrokAIService::class),
                $app->make(TransactionParserService::class)
            );
        });

        $this->app->bind(TelegramWebhookService::class, function ($app) {
            return new TelegramWebhookService(
                $app->make(TelegramBotService::class),
                $app->make(TransactionParserService::class),
                $app->make(ReceiptScannerService::class),
                $app->make(VoiceNoteTranscriptionService::class),
                $app->make(GrokAIService::class)
            );
        });

        $this->app->bind(TelegramWebhookService::class, function ($app) {
            return new TelegramWebhookService(
                $app->make(TelegramBotService::class),
                $app->make(TransactionParserService::class),
                $app->make(ReceiptScannerService::class),
                $app->make(VoiceNoteTranscriptionService::class),
                $app->make(GrokAIService::class)
            );
        });
    }

    public function boot(): void
    {
        Paginator::useTailwind();
    }
}
