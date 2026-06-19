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
<<<<<<< Updated upstream

    // ── AI / Groq ────────────────────────────────────────────────────────────
    'ai' => [
        'provider' => env('AI_PROVIDER', 'groq'),
=======
    'ai' => [
        'provider' => env('AI_PROVIDER', 'groq'), // groq, grok, openai
>>>>>>> Stashed changes
    ],
    'groq' => [
        'api_key'      => env('GROQ_API_KEY'),
        'model'        => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
        'vision_model' => env('GROQ_VISION_MODEL', 'meta-llama/llama-4-scout-17b-16e-instruct'),
        'base_url'     => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
    ],
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],
<<<<<<< Updated upstream

    // ── Telegram Bot ─────────────────────────────────────────────────────────
=======
>>>>>>> Stashed changes
    'telegram' => [
        'bot_token'      => env('TELEGRAM_BOT_TOKEN'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'bot_username'   => env('TELEGRAM_BOT_USERNAME'),
<<<<<<< Updated upstream
=======
    ],
    'whatsapp' => [
        'gateway_url'    => env('WHATSAPP_GATEWAY_URL'),
        'api_key'        => env('WHATSAPP_API_KEY'),
        'device_id'      => env('WHATSAPP_DEVICE_ID'),
        'sender_number'  => env('WHATSAPP_SENDER_NUMBER'),
        'webhook_secret' => env('WHATSAPP_WEBHOOK_SECRET'),
>>>>>>> Stashed changes
    ],
];
