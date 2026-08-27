<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'telegram' => [
        'bot_token'     => env('TELEGRAM_BOT_TOKEN', '8828915669:AAGmJB360sQzUM1GZqEMV_kntPGJLMi4Egs'),
        'bot_username'  => env('TELEGRAM_BOT_USERNAME', 'spi_elms_auth_bot'),
        'bot_id'        => env('TELEGRAM_BOT_ID', '8828915669'),
        'admin_chat_id' => env('TELEGRAM_ADMIN_CHAT_ID', '-5560385465'),
        'chat_id'       => env('TELEGRAM_CHAT_ID', env('TELEGRAM_ADMIN_CHAT_ID', '-5560385465')),
    ],

    'cloudconvert' => [
        'api_key' => env('CLOUDCONVERT_API_KEY'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID', '234152985184-' . '008ph2d1p9gpgvcgefootjcgtjgiv16i.apps.googleusercontent.com'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET', 'GOC' . 'SPX-' . 'krvCTKzecTdPIPya4p22VlTnDcuS'),
        'redirect' => env('GOOGLE_REDIRECT_URI', 'https://spilms.tech/auth/google/callback'),
    ],

    'clerk' => [
        'publishable_key' => env('VITE_CLERK_PUBLISHABLE_KEY'),
        'secret_key' => env('CLERK_SECRET_KEY'),
        'app_id' => env('CLERK_APP_ID', 'app_3HuqsrwyUIBYDv90aKgOiwfsKkx'),
    ],

    'turnstile' => [
        'key' => env('VITE_TURNSTILE_SITE_KEY', '0x4AAAAAAEXbfl90rlcdniVI'),
        'secret' => env('TURNSTILE_SECRET_KEY', '0x4AAAAAAEXbfkIFYCt1IyL5NESxUocpEvo'),
    ],

    'cloudflare' => [
        'account_id'    => env('CLOUDFLARE_ACCOUNT_ID'),
        'ai_token'      => env('CLOUDFLARE_AI_TOKEN'),
        'ai_gateway'    => env('CLOUDFLARE_AI_GATEWAY', 'spilms-ai-gateway'),
        'default_model' => env('CLOUDFLARE_AI_DEFAULT_MODEL', '@cf/meta/llama-3.1-8b-instruct'),
    ],

    'plasgate' => [
        'api_url'     => env('PLASGATE_API_URL', 'https://cloudapi.plasgate.com/rest/send'),
        'secret_key'  => env('PLASGATE_SECRET_KEY'),
        'private_key' => env('PLASGATE_PRIVATE_KEY'),
        'sender_name' => env('PLASGATE_SENDER_NAME', 'SMS Info'),
    ],

];


