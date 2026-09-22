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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

    'translation' => [
        // The local LibreTranslate service has no per-request or daily quota.
        // DeepL and MyMemory remain available when explicitly configured.
        'provider' => env('TRANSLATION_PROVIDER', 'libretranslate'),
        'timeout' => (int) env('TRANSLATION_TIMEOUT', 30),
        'libretranslate' => [
            'url' => env('LIBRETRANSLATE_URL', 'http://127.0.0.1:5000/translate'),
        ],
        'mymemory' => [
            'url' => env('MYMEMORY_TRANSLATE_URL', 'https://api.mymemory.translated.net/get'),
            // MyMemory grants a larger daily allowance when requests include a
            // valid contact address. A dedicated value can override mail-from.
            'email' => env('MYMEMORY_TRANSLATE_EMAIL', env('MAIL_FROM_ADDRESS')),
        ],
        'deepl' => [
            'url' => env('DEEPL_TRANSLATE_URL', 'https://api-free.deepl.com/v2/translate'),
            'key' => env('DEEPL_API_KEY'),
        ],
    ],
];
