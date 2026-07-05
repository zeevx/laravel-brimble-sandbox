<?php

declare(strict_types=1);

return [
    'api_key' => env('BRIMBLE_SANDBOX_KEY'),

    'base_url' => env('BRIMBLE_SANDBOX_BASE_URL', 'https://sandbox.brimble.io'),

    'timeout' => (float) env('BRIMBLE_SANDBOX_TIMEOUT', 90),

    'max_retries' => (int) env('BRIMBLE_SANDBOX_MAX_RETRIES', 2),
];
