<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [
    'name'            => env('APP_NAME', 'Finance AI'),
    'env'             => env('APP_ENV', 'production'),
    'debug'           => (bool) env('APP_DEBUG', false),
    'url'             => env('APP_URL', 'http://localhost'),
    'asset_url'       => env('ASSET_URL'),
    'timezone'        => env('APP_TIMEZONE', 'Asia/Jakarta'),
    'locale'          => env('APP_LOCALE', 'id'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale'    => env('APP_FAKER_LOCALE', 'id_ID'),
    'key'             => env('APP_KEY'),
    'cipher'          => 'AES-256-CBC',
    'maintenance'     => ['driver' => 'file'],
    'providers'       => ServiceProvider::defaultProviders()->merge([
        App\Providers\AppServiceProvider::class,
    ])->toArray(),
    'aliases' => Facade::defaultAliases()->merge([
        'PDF' => Barryvdh\DomPDF\Facade\Pdf::class,
        'Excel' => Maatwebsite\Excel\Facades\Excel::class,
    ])->toArray(),
];
