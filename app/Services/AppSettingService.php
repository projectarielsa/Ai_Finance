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

    public function getGrokCredential(): ?ApiCredential
    {
        return ApiCredential::getDefault('grok');
    }

    public function getDefaultGateway(): ?WhatsappGateway
    {
        return WhatsappGateway::getDefault();
    }

    public function getGrokApiKey(): ?string
    {
        $cred = $this->getGrokCredential();
        return $cred?->key_value ?? config('services.grok.api_key');
    }

    public function getGrokModel(): string
    {
        $cred = $this->getGrokCredential();
        return $cred?->model ?? config('services.grok.model', 'grok-2-vision-1212');
    }

    public function getGrokBaseUrl(): string
    {
        $cred = $this->getGrokCredential();
        return $cred?->endpoint_url ?? config('services.grok.base_url', 'https://api.x.ai/v1');
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
