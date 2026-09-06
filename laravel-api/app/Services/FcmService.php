<?php

namespace App\Services;

use App\Models\StudentDeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FcmService
{
    private ?string $accessToken = null;

    private ?int $accessTokenExpiresAt = null;

    public function isConfigured(): bool
    {
        $credentials = $this->credentials();

        return ! empty($credentials['project_id'])
            && ! empty($credentials['client_email'])
            && ! empty($credentials['private_key']);
    }

    /**
     * @param  array<int, int|string>  $studentIds
     * @return array{sent:int, failed:int, skipped:int}
     */
    public function sendToStudents(array $studentIds, string $title, string $body, array $data = []): array
    {
        $studentIds = array_values(array_unique(array_filter(array_map('intval', $studentIds))));
        if ($studentIds === []) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0];
        }

        if (! $this->isConfigured()) {
            Log::warning('FCM is not configured. Skipping push send.');

            return ['sent' => 0, 'failed' => 0, 'skipped' => count($studentIds)];
        }

        $tokens = StudentDeviceToken::query()
            ->whereIn('student_id', $studentIds)
            ->pluck('token')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($tokens === []) {
            Log::warning('FCM skip: no device tokens for students', [
                'student_ids' => $studentIds,
            ]);

            return ['sent' => 0, 'failed' => 0, 'skipped' => count($studentIds)];
        }

        $sent = 0;
        $failed = 0;
        $dataPayload = [];
        foreach ($data as $key => $value) {
            $dataPayload[(string) $key] = (string) $value;
        }

        foreach ($tokens as $token) {
            try {
                $ok = $this->sendToToken((string) $token, $title, $body, $dataPayload);
                if ($ok) {
                    $sent++;
                } else {
                    $failed++;
                }
            } catch (Throwable $e) {
                $failed++;
                Log::warning('FCM send failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'skipped' => 0];
    }

    /**
     * @param  array<string, string>  $data
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        $projectId = $this->credentials()['project_id'];
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(20)
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $data,
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'channel_id' => 'notices',
                            'click_action' => 'OPEN_NOTICES',
                        ],
                    ],
                ],
            ]);

        if ($response->successful()) {
            return true;
        }

        $errorCode = data_get($response->json(), 'error.details.0.errorCode')
            ?? data_get($response->json(), 'error.status');

        // Drop invalid / unregistered tokens so we do not keep failing forever.
        if (in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT', 'NOT_FOUND'], true)
            || str_contains(strtolower((string) $response->body()), 'not a valid fcm registration token')
            || str_contains(strtolower((string) $response->body()), 'requested entity was not found')) {
            StudentDeviceToken::query()->where('token', $token)->delete();
        }

        Log::warning('FCM HTTP error', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return false;
    }

    private function getAccessToken(): string
    {
        if ($this->accessToken && $this->accessTokenExpiresAt && time() < $this->accessTokenExpiresAt - 60) {
            return $this->accessToken;
        }

        $credentials = $this->credentials();
        $now = time();
        $jwtHeader = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $jwtClaim = $this->base64UrlEncode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));

        $unsigned = $jwtHeader.'.'.$jwtClaim;
        $privateKey = openssl_pkey_get_private($credentials['private_key']);
        if ($privateKey === false) {
            throw new \RuntimeException('Invalid FCM private key.');
        }

        $signature = '';
        $signed = openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (! $signed) {
            throw new \RuntimeException('Unable to sign FCM JWT.');
        }

        $assertion = $unsigned.'.'.$this->base64UrlEncode($signature);

        $response = Http::asForm()
            ->timeout(20)
            ->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);

        if (! $response->successful() || empty($response->json('access_token'))) {
            throw new \RuntimeException('Unable to obtain FCM access token: '.$response->body());
        }

        $this->accessToken = (string) $response->json('access_token');
        $this->accessTokenExpiresAt = $now + (int) ($response->json('expires_in') ?? 3600);

        return $this->accessToken;
    }

    /**
     * @return array{project_id:?string, client_email:?string, private_key:?string}
     */
    private function credentials(): array
    {
        $path = config('services.fcm.credentials');
        if (is_string($path) && $path !== '' && is_file($path)) {
            $json = json_decode((string) file_get_contents($path), true);
            if (is_array($json)) {
                return [
                    'project_id' => $json['project_id'] ?? config('services.fcm.project_id'),
                    'client_email' => $json['client_email'] ?? null,
                    'private_key' => isset($json['private_key'])
                        ? str_replace('\\n', "\n", (string) $json['private_key'])
                        : null,
                ];
            }
        }

        $privateKey = config('services.fcm.private_key');
        if (is_string($privateKey)) {
            $privateKey = str_replace('\\n', "\n", $privateKey);
        }

        return [
            'project_id' => config('services.fcm.project_id'),
            'client_email' => config('services.fcm.client_email'),
            'private_key' => $privateKey,
        ];
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
