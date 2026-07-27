<?php

return [
    'api_version' => '1.0',
    'token_expiration_days' => (int) env('MOBILE_TOKEN_EXPIRATION_DAYS', 30),
    'android' => [
        'latest_version' => env('MOBILE_ANDROID_LATEST_VERSION', '1.0.0'),
        'latest_build' => (int) env('MOBILE_ANDROID_LATEST_BUILD', 1),
        'minimum_version' => env('MOBILE_ANDROID_MINIMUM_VERSION', '1.0.0'),
        'minimum_build' => (int) env('MOBILE_ANDROID_MINIMUM_BUILD', 1),
        'distribution_url' => env('MOBILE_ANDROID_DISTRIBUTION_URL'),
    ],
    'maintenance' => [
        'enabled' => (bool) env('MOBILE_MAINTENANCE_ENABLED', false),
        'message' => env('MOBILE_MAINTENANCE_MESSAGE'),
    ],
    'notifications' => [
        'inbox_retention_days' => (int) env('MOBILE_NOTIFICATION_RETENTION_DAYS', 180),
        'delivery_retention_days' => (int) env('MOBILE_DELIVERY_RETENTION_DAYS', 30),
        'device_stale_days' => (int) env('MOBILE_DEVICE_STALE_DAYS', 90),
    ],
];
