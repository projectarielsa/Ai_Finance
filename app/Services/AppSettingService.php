<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\ApiCredential;
use Illuminate\Support\Facades\Cache;

class AppSettingService
{
    public function get(string $key, mixed $default = null): mixed
    {
        return AppSetting::get($key, $default);
    }

    public function set(string $key, mixed $value, string $type = 'string'): void
    {
        AppSetting::set($key, $value, $type);
    }

    public function getAiProvider(): string
    {
        return config('services.ai.provider', 'groq');
<<<<<<< Updated upstream
=======
    }

    public function getAiCredential(): ?ApiCredential
    {
        $provider = $this->getAiProvider();
        return ApiCredential::getDefault($provider);
>>>>>>> Stashed changes
    }

    public function getAiCredential(): ?ApiCredential
    {
        $provider = $this->getAiProvider();
        return ApiCredential::getDefault($provider);
    }

    public function getAiApiKey(): ?string
    {
        $cred = $this->getAiCredential();
<<<<<<< Updated upstream
        if ($cred?->key_value) return $cred->key_value;

        // Fallback to env
        return config('services.groq.api_key')
            ?? env('GROQ_API_KEY');
=======
        return $cred?->key_value ?? config('services.groq.api_key');
>>>>>>> Stashed changes
    }

    public function getAiModel(): string
    {
        $cred = $this->getAiCredential();
        return $cred?->model ?? config('services.groq.model', 'llama-3.3-70b-versatile');
    }

    public function getAiVisionModel(): string
    {
        $cred = $this->getAiCredential();
        $meta = $cred?->meta;
        return $meta['vision_model'] ?? config('services.groq.vision_model', 'meta-llama/llama-4-scout-17b-16e-instruct');
<<<<<<< Updated upstream
=======
    }

    public function getAiBaseUrl(): string
    {
        $cred = $this->getAiCredential();
        return $cred?->endpoint_url ?? config('services.groq.base_url', 'https://api.groq.com/openai/v1');
    }

    public function getTelegramToken(): ?string
    {
        return config('services.telegram.bot_token');
>>>>>>> Stashed changes
    }

    public function getAiBaseUrl(): string
    {
        $cred = $this->getAiCredential();
        return $cred?->endpoint_url ?? config('services.groq.base_url', 'https://api.groq.com/openai/v1');
    }

    public function getTelegramToken(): ?string
    {
        return config('services.telegram.bot_token');
    }

    public function clearCache(): void
    {
        // AppSetting::get() menggunakan cache key "setting_{$key}"
        // ApiCredential::getDefault() tidak menggunakan cache (query langsung)
        // Forget semua setting key yang umum dipakai
        $keys = [
            'ai_provider', 'ai_model', 'ai_vision_model', 'ai_base_url',
            'groq_api_key', 'telegram_bot_token', 'telegram_webhook_secret',
            'app_name', 'app_url', 'timezone', 'currency',
        ];
        foreach ($keys as $key) {
            Cache::forget("setting_{$key}");
        }
    }
}
