<?php

return [
    'enabled' => env('IMAGE_OPTIMIZER_ENABLED', true),
    'url' => env('IMAGE_OPTIMIZER_URL', 'http://127.0.0.1:8011'),
    'timeout' => env('IMAGE_OPTIMIZER_TIMEOUT', 15),
    'quality' => env('IMAGE_WEBP_QUALITY', 80),
    'max_width' => env('IMAGE_MAX_WIDTH', 1920),
    'max_height' => env('IMAGE_MAX_HEIGHT', 1920),
    'thumb_width' => env('IMAGE_THUMB_WIDTH', 600),
    'max_upload_bytes' => env('IMAGE_MAX_UPLOAD_BYTES', 20 * 1024 * 1024),
];
