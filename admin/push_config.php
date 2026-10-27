<?php
/**
 * Push notification bridge settings (admin -> Laravel API).
 * Keep push_secret in sync with laravel-api .env FCM_PUSH_SECRET.
 */
return [
    // Production API (must match the app API base URL + /push/notices)
    'api_push_url' => 'https://deyeducation.cloud/api/api/push/notices',
    // If SSL not ready: 'http://deyeducation.cloud/api/api/push/notices'
    // Local XAMPP: 'http://127.0.0.1/admin/laravel-api/public/api/push/notices'
    'push_secret' => 'change-me-push-secret',
    // Write admin/push_debug.log for troubleshooting
    'debug' => true,
];
