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

    'mailtrap' => [
        'api_token' => env('MAILTRAP_API_TOKEN'),
        'from_address' => env('MAILTRAP_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', 'hello@example.com')),
        'from_name' => env('MAILTRAP_FROM_NAME', env('MAIL_FROM_NAME', env('APP_NAME', 'Laravel'))),
        'category' => env('MAILTRAP_CATEGORY', 'Back in Stock'),
        'bulk' => (bool) env('MAILTRAP_BULK', true),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'product_import' => [
        'free_ecommerce_url' => env('FREE_ECOMMERCE_PRODUCTS_URL', 'https://kolzsticks.github.io/Free-Ecommerce-Products-Api/main/products.json'),
        'escuelajs_url' => env('ESCUELAJS_PRODUCTS_URL', 'https://api.escuelajs.co/api/v1/products'),
        'route_misr_url' => env('ROUTE_MISR_PRODUCTS_URL', 'https://ecommerce.routemisr.com/api/v1/products'),
        'max_attempts' => (int) env('PRODUCT_IMPORT_MAX_ATTEMPTS', 3),
        'timeout_seconds' => (int) env('PRODUCT_IMPORT_TIMEOUT_SECONDS', 30),
        'image_download_attempts' => (int) env('PRODUCT_IMPORT_IMAGE_ATTEMPTS', 3),
        'image_timeout_seconds' => (int) env('PRODUCT_IMPORT_IMAGE_TIMEOUT_SECONDS', 60),
        'retry_base_sleep_ms' => (int) env('PRODUCT_IMPORT_RETRY_SLEEP_MS', 250),
    ],

];
