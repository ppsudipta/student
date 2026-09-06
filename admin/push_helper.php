<?php
/**
 * Fire-and-forget push to Laravel after notices are saved.
 * Configure LARAVEL_API_PUSH_URL and FCM_PUSH_SECRET in admin/push_config.php
 */
function send_notice_push(array $student_ids, string $notice_type, string $notice_content): void
{
    $student_ids = array_values(array_unique(array_filter(array_map('intval', $student_ids))));
    if ($student_ids === []) {
        return;
    }

    $configFile = __DIR__ . '/push_config.php';
    $config = is_file($configFile) ? include $configFile : [];
    if (! is_array($config)) {
        $config = [];
    }

    $apiUrl = rtrim((string) ($config['api_push_url'] ?? 'http://127.0.0.1/admin/laravel-api/public/api/push/notices'), '/');
    $secret = (string) ($config['push_secret'] ?? 'change-me-push-secret');

    if ($notice_type === 'text') {
        $body = trim($notice_content);
        if (strlen($body) > 180) {
            $body = substr($body, 0, 177) . '...';
        }
        if ($body === '') {
            $body = 'You have a new notice.';
        }
    } elseif ($notice_type === 'image') {
        $body = 'New image notice';
    } elseif ($notice_type === 'video') {
        $body = 'New video notice';
    } else {
        $body = 'You have a new notice.';
    }

    $payload = json_encode([
        'student_ids' => $student_ids,
        'title' => 'New notice',
        'body' => $body,
        'notice_type' => $notice_type,
        'secret' => $secret,
    ]);

    if ($payload === false) {
        return;
    }

    // Prefer cURL when available.
    if (function_exists('curl_init')) {
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-Push-Secret: ' . $secret,
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);
        curl_exec($ch);
        curl_close($ch);
        return;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nAccept: application/json\r\nX-Push-Secret: {$secret}\r\n",
            'content' => $payload,
            'timeout' => 8,
            'ignore_errors' => true,
        ],
    ]);
    @file_get_contents($apiUrl, false, $context);
}
