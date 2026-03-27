<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Authentication
    |--------------------------------------------------------------------------
    | Built-in admin auth: set ADMIN_EMAIL and ADMIN_PASSWORD in .env
    | Keycloak: set AUTH_API_URL in .env (optional)
    */
    'admin_email' => env('ADMIN_EMAIL', ''),
    'admin_password' => env('ADMIN_PASSWORD', ''),
    'auth_api_url' => env('AUTH_API_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Telegram Bot
    |--------------------------------------------------------------------------
    */
    'telegram_bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
    'telegram_chat_id' => env('TELEGRAM_CHAT_ID', ''),
    'telegram_webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Widget
    |--------------------------------------------------------------------------
    */
    'widget_domain' => env('WIDGET_DOMAIN', 'https://lchat.shaul.kr'),

    /*
    |--------------------------------------------------------------------------
    | Monitoring
    |--------------------------------------------------------------------------
    */
    'enable_metrics' => env('ENABLE_METRICS', false),
];
