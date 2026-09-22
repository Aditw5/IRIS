<?php

return [
    'provider' => env('WHATSAPP_PROVIDER', 'wablas'),
    'url' => env('WABLAS_URL', 'https://sby.wablas.com/api/send-message'),
    'token' => env('WABLAS_TOKEN', 'jEdiLI6VSWDzZfTQV2WKtkV6iA8eNTzhGTREoAOEhmpjMTM7uIaM5ll'),
    'secret_key' => env('WABLAS_SECRET_KEY', 'EpxyT46x'),
    'timeout' => (int) env('WABLAS_TIMEOUT', 10),
    'connect_timeout' => (int) env('WABLAS_CONNECT_TIMEOUT', 5),
    'pengingat_cooldown_hours' => (int) env('WABLAS_PENGINGAT_COOLDOWN_HOURS', 24),
    'rekalibrasi_min_delay_seconds' => (int) env('WABLAS_REKALIBRASI_MIN_DELAY_SECONDS', 90),
    'rekalibrasi_max_delay_seconds' => (int) env('WABLAS_REKALIBRASI_MAX_DELAY_SECONDS', 150),
];
