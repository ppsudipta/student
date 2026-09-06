<?php
/**
 * Push notification bridge settings (admin -> Laravel API).
 * Keep push_secret in sync with laravel-api .env FCM_PUSH_SECRET.
 */
return [
    // Local XAMPP example:
    'api_push_url' => 'http://127.0.0.1/admin/laravel-api/public/api/push/notices',
    // Production example (adjust to your live Laravel API URL):
    // 'api_push_url' => 'http://187.127.187.70/api/api/push/notices',
    'push_secret' => 'change-me-push-secret',
];
