<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\ApiCredential;
use App\Models\WhatsappGateway;
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
    }

    public function getAiCredential(): ?ApiCredential
    {
        $provider = $this->getAiProvider();
        return ApiCredential::getDefault($provider);
    }

    public function getDefaultGateway(): ?WhatsappGateway
    {
        return WhatsappGateway::getDefault();
    }

    public function getAiApiKey(): ?string
    {
        $cred = $this->getAiCredential();
        return $cred?->key_value ?? config('services.groq.api_key');
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
    }

    public function getAiBaseUrl(): string
    {
        $cred = $this->getAiCredential();
        return $cred?->endpoint_url ?? config('services.groq.base_url', 'https://api.groq.com/openai/v1');
    }

    public function getWhatsappGateway(): ?WhatsappGateway
    {
        return Cache::remember('default_wa_gateway', 300, fn() => $this->getDefaultGateway());
    }

    public function clearCache(): void
    {
        Cache::flush();
    }
}
