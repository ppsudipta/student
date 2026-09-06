<?php
/**
 * Fire-and-forget push to Laravel after notices are saved.
 * Configure api_push_url / push_secret in admin/push_config.php
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

    $apiUrl = rtrim((string) ($config['api_push_url'] ?? 'http://187.127.187.70/api/api/push/notices'), '/');
    $secret = (string) ($config['push_secret'] ?? 'change-me-push-secret');
    $debug = ! empty($config['debug']);

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

    $httpCode = 0;
    $responseBody = '';
    $curlError = '';

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
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $responseBody = (string) curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = (string) curl_error($ch);
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAccept: application/json\r\nX-Push-Secret: {$secret}\r\n",
                'content' => $payload,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);
        $responseBody = (string) @file_get_contents($apiUrl, false, $context);
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $httpCode = (int) $m[1];
        }
    }

    if ($debug) {
        $line = sprintf(
            "[%s] url=%s students=%s http=%s curl_error=%s response=%s\n",
            date('Y-m-d H:i:s'),
            $apiUrl,
            implode(',', $student_ids),
            $httpCode,
            $curlError,
            $responseBody
        );
        @file_put_contents(__DIR__ . '/push_debug.log', $line, FILE_APPEND);
    }
}
