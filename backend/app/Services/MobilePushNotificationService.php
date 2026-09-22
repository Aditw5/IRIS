<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MobilePushNotificationService
{
    private function mobileDb()
    {
        return DB::connection(env('MOBILE_DB_CONNECTION', 'pgsql_mobile'));
    }

    public function sendToUser($userId, string $title, string $body, array $data = [])
    {
        $tokens = $this->mobileDb()
            ->table('mobile_push_tokens')
            ->where('user_id', $userId)
            ->where('user_type', 'customer')
            ->where('is_active', true)
            ->pluck('fcm_token')
            ->filter()
            ->unique()
            ->values();

        if ($tokens->isEmpty()) {
            return [
                'status' => false,
                'message' => 'Token FCM user tidak ditemukan',
                'database' => $this->mobileDb()->getDatabaseName(),
                'user_id' => $userId,
                'total_token' => 0,
                'results' => [],
            ];
        }

        $results = [];

        foreach ($tokens as $token) {
            $results[] = $this->sendToToken($token, $title, $body, $data);
        }

        return [
            'status' => true,
            'message' => 'FCM berhasil dikirim ke user',
            'database' => $this->mobileDb()->getDatabaseName(),
            'user_id' => $userId,
            'total_token' => $tokens->count(),
            'results' => $results,
        ];
    }

    public function sendToMitra($mitraFk, string $title, string $body, array $data = [])
    {
        $userIds = $this->mobileDb()
            ->table('users')
            ->whereRaw('CAST(mitrafk AS TEXT) = ?', [(string) $mitraFk])
            ->pluck('id')
            ->filter()
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return [
                'status' => false,
                'message' => 'Tidak ada customer dengan mitrafk tersebut',
                'database' => $this->mobileDb()->getDatabaseName(),
                'mitrafk' => $mitraFk,
                'total_user' => 0,
                'total_token' => 0,
                'results' => [],
            ];
        }

        $tokens = $this->mobileDb()
            ->table('mobile_push_tokens')
            ->whereIn('user_id', $userIds)
            ->where('user_type', 'customer')
            ->where('is_active', true)
            ->pluck('fcm_token')
            ->filter()
            ->unique()
            ->values();

        if ($tokens->isEmpty()) {
            return [
                'status' => false,
                'message' => 'Customer ditemukan, tetapi token FCM aktif tidak ada',
                'database' => $this->mobileDb()->getDatabaseName(),
                'mitrafk' => $mitraFk,
                'total_user' => $userIds->count(),
                'total_token' => 0,
                'results' => [],
            ];
        }

        $results = [];

        foreach ($tokens as $token) {
            $results[] = $this->sendToToken(
                $token,
                $title,
                $body,
                array_merge($data, [
                    'mitrafk' => (string) $mitraFk,
                ])
            );
        }

        return [
            'status' => true,
            'message' => 'FCM berhasil dikirim ke customer berdasarkan mitrafk',
            'database' => $this->mobileDb()->getDatabaseName(),
            'mitrafk' => $mitraFk,
            'total_user' => $userIds->count(),
            'total_token' => $tokens->count(),
            'results' => $results,
        ];
    }

    public function sendToAdminsByLocation($locationId, string $title, string $body, array $data = [])
    {
        $userIds = $this->mobileDb()
            ->table('loginuser_s as login')
            ->join('pegawai_m as pegawai', 'pegawai.id', '=', 'login.objectpegawaifk')
            ->where('login.statusenabled', true)
            ->where('pegawai.statusenabled', true)
            ->where('pegawai.objectjenispegawaifk', 13)
            ->whereRaw('CAST(pegawai.lokasikalibrasifk AS TEXT) = ?', [(string) $locationId])
            ->pluck('login.id')
            ->filter()
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return [
                'status' => false,
                'message' => 'Akun admin pada lokasi tersebut tidak ditemukan',
                'database' => $this->mobileDb()->getDatabaseName(),
                'location_id' => $locationId,
                'total_user' => 0,
                'total_token' => 0,
                'results' => [],
            ];
        }

        $tokens = $this->mobileDb()
            ->table('mobile_push_tokens')
            ->whereIn('user_id', $userIds)
            ->whereRaw("LOWER(TRIM(user_type)) IN ('admin', 'administrator')")
            ->where('is_active', true)
            ->pluck('fcm_token')
            ->filter()
            ->unique()
            ->values();

        if ($tokens->isEmpty()) {
            return [
                'status' => false,
                'message' => 'Admin ditemukan, tetapi token FCM aktif tidak ada',
                'database' => $this->mobileDb()->getDatabaseName(),
                'location_id' => $locationId,
                'total_user' => $userIds->count(),
                'total_token' => 0,
                'results' => [],
            ];
        }

        $results = [];

        foreach ($tokens as $token) {
            $results[] = $this->sendToToken(
                $token,
                $title,
                $body,
                array_merge($data, [
                    'location_id' => (string) $locationId,
                ])
            );
        }

        return [
            'status' => true,
            'message' => 'FCM berhasil dikirim ke admin berdasarkan lokasi',
            'database' => $this->mobileDb()->getDatabaseName(),
            'location_id' => $locationId,
            'total_user' => $userIds->count(),
            'total_token' => $tokens->count(),
            'results' => $results,
        ];
    }

    public function sendToToken(string $fcmToken, string $title, string $body, array $data = [])
    {
        $projectId = env('FIREBASE_PROJECT_ID');

        if (!$projectId) {
            throw new \Exception('FIREBASE_PROJECT_ID belum diset di .env');
        }

        $accessToken = $this->getFirebaseAccessToken();

        $normalizedData = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                $normalizedData[$key] = '';
            } elseif (is_array($value) || is_object($value)) {
                $normalizedData[$key] = json_encode($value);
            } else {
                $normalizedData[$key] = (string) $value;
            }
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $payload = [
            'message' => [
                'token' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $normalizedData,
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'channel_id' => 'ulab_high_importance_channel',
                        'sound' => 'default',
                    ],
                ],
            ],
        ];

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->post($url, $payload);

        $responseBody = $response->json();

        if (!$response->successful()) {
            Log::warning('FCM send failed', [
                'status' => $response->status(),
                'body' => $responseBody,
            ]);

            $errorCode = $responseBody['error']['details'][0]['errorCode'] ?? null;

            if (in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT'])) {
                $this->mobileDb()
                    ->table('mobile_push_tokens')
                    ->where('fcm_token', $fcmToken)
                    ->update([
                        'is_active' => false,
                        'updated_at' => now(),
                    ]);
            }
        }

        return [
            'success' => $response->successful(),
            'http_status' => $response->status(),
            'response' => $responseBody,
        ];
    }

    private function getFirebaseAccessToken()
    {
        $serviceAccountPath = env('FIREBASE_SERVICE_ACCOUNT_JSON');

        if (!$serviceAccountPath) {
            throw new \Exception('FIREBASE_SERVICE_ACCOUNT_JSON belum diset di .env');
        }

        $fullPath = storage_path('app/' . $serviceAccountPath);

        if (!file_exists($fullPath)) {
            throw new \Exception('File service account Firebase tidak ditemukan: ' . $fullPath);
        }

        $serviceAccount = json_decode(file_get_contents($fullPath), true);

        if (!$serviceAccount) {
            throw new \Exception('File service account Firebase tidak valid');
        }

        $clientEmail = $serviceAccount['client_email'] ?? null;
        $privateKey = $serviceAccount['private_key'] ?? null;

        if (!$clientEmail || !$privateKey) {
            throw new \Exception('client_email atau private_key tidak ditemukan di service account');
        }

        $now = time();

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $claims = [
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $jwtHeader = $this->base64UrlEncode(json_encode($header));
        $jwtClaims = $this->base64UrlEncode(json_encode($claims));
        $unsignedJwt = $jwtHeader . '.' . $jwtClaims;

        $signature = '';

        $ok = openssl_sign(
            $unsignedJwt,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256
        );

        if (!$ok) {
            throw new \Exception('Gagal membuat signature JWT Firebase');
        }

        $jwt = $unsignedJwt . '.' . $this->base64UrlEncode($signature);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (!$response->successful()) {
            Log::error('Gagal ambil Firebase access token', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new \Exception('Gagal ambil Firebase access token: ' . $response->body());
        }

        $accessToken = $response->json('access_token');

        if (!$accessToken) {
            throw new \Exception('Firebase access_token kosong');
        }

        return $accessToken;
    }

    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
