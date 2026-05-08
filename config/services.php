<?php

return [
    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme'   => 'https',
    ],
    'postmark' => ['token' => env('POSTMARK_TOKEN')],
    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'grok' => [
        'api_key'  => env('GROK_API_KEY'),
        'model'    => env('GROK_MODEL', 'grok-2-vision-1212'),
        'base_url' => env('GROK_BASE_URL', 'https://api.x.ai/v1'),
    ],
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],
    'whatsapp' => [
        'gateway_url'    => env('WHATSAPP_GATEWAY_URL'),
        'api_key'        => env('WHATSAPP_API_KEY'),
        'device_id'      => env('WHATSAPP_DEVICE_ID'),
        'sender_number'  => env('WHATSAPP_SENDER_NUMBER'),
        'webhook_secret' => env('WHATSAPP_WEBHOOK_SECRET'),
    ],
];
