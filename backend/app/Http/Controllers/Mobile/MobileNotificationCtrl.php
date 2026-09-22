<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MobileNotificationCtrl extends Controller
{
    private function mobileDb()
    {
        return DB::connection('pgsql_mobile');
    }

    public function registerFcmToken(Request $request)
    {
        try {
            $userId = $request->input('user_id');
            $userType = $request->input('user_type', 'customer');
            $fcmToken = $request->input('fcm_token');
            $deviceId = $request->input('device_id');
            $platform = $request->input('platform', 'android');

            if (!$userId) {
                return response()->json([
                    'status' => false,
                    'message' => 'user_id wajib diisi',
                ], 400);
            }

            if (!$fcmToken) {
                return response()->json([
                    'status' => false,
                    'message' => 'fcm_token wajib diisi',
                ], 400);
            }

            $existing = $this->mobileDb()
                ->table('mobile_push_tokens')
                ->where('fcm_token', $fcmToken)
                ->first();

            if ($existing) {
                $this->mobileDb()
                    ->table('mobile_push_tokens')
                    ->where('id', $existing->id)
                    ->update([
                        'user_id' => $userId,
                        'user_type' => $userType,
                        'device_id' => $deviceId,
                        'platform' => $platform,
                        'is_active' => true,
                        'last_used_at' => now(),
                        'updated_at' => now(),
                    ]);
            } else {
                $this->mobileDb()
                    ->table('mobile_push_tokens')
                    ->insert([
                        'user_id' => $userId,
                        'user_type' => $userType,
                        'fcm_token' => $fcmToken,
                        'device_id' => $deviceId,
                        'platform' => $platform,
                        'is_active' => true,
                        'last_used_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'FCM token berhasil disimpan',
                'database' => $this->mobileDb()->getDatabaseName(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal simpan FCM token mobile', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Gagal simpan FCM token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function testSendFcmToUser(Request $request)
    {
        try {
            $secret = $request->input('secret');

            if ($secret !== env('MOBILE_PUSH_TEST_SECRET')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Secret tidak valid',
                ], 403);
            }

            $userId = $request->input('user_id');
            $title = $request->input('title', 'Test Notifikasi U-LAB');
            $body = $request->input('body', 'Notifikasi dari backend Laravel berhasil dikirim.');

            if (!$userId) {
                return response()->json([
                    'status' => false,
                    'message' => 'user_id wajib diisi',
                ], 400);
            }

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
                return response()->json([
                    'status' => false,
                    'message' => 'Token FCM user tidak ditemukan',
                    'database' => $this->mobileDb()->getDatabaseName(),
                ], 404);
            }

            $results = [];

            foreach ($tokens as $token) {
                $results[] = $this->sendFcmToToken(
                    $token,
                    $title,
                    $body,
                    [
                        'type' => 'test_notification',
                        'user_id' => (string) $userId,
                        'source' => 'laravel_backend',
                    ]
                );
            }

            return response()->json([
                'status' => true,
                'message' => 'Test push notification diproses',
                'database' => $this->mobileDb()->getDatabaseName(),
                'total_token' => count($results),
                'results' => $results,
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal test kirim FCM user', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Gagal test kirim FCM',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function testSendFcmToMitra(Request $request)
    {
        try {
            $secret = $request->input('secret');

            if ($secret !== env('MOBILE_PUSH_TEST_SECRET')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Secret tidak valid',
                ], 403);
            }

            $mitraFk = $request->input('mitrafk');
            $title = $request->input('title', 'Test Notifikasi Unit U-LAB');
            $body = $request->input('body', 'Notifikasi untuk semua customer pada unit ini berhasil dikirim.');

            if (!$mitraFk) {
                return response()->json([
                    'status' => false,
                    'message' => 'mitrafk wajib diisi',
                ], 400);
            }

            $result = $this->sendFcmToCustomersByMitra(
                $mitraFk,
                $title,
                $body,
                [
                    'type' => 'test_mitra_notification',
                    'mitrafk' => (string) $mitraFk,
                    'source' => 'laravel_backend',
                ]
            );

            return response()->json([
                'status' => true,
                'message' => 'Test push notification by mitrafk diproses',
                'database' => $this->mobileDb()->getDatabaseName(),
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal test kirim FCM mitra', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Gagal test kirim FCM mitra',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function sendFcmToCustomersByMitra($mitraFk, string $title, string $body, array $data = [])
    {
        $userIds = $this->mobileDb()
            ->table('users')
            ->whereRaw('CAST(mitrafk AS TEXT) = ?', [(string) $mitraFk])
            ->pluck('id')
            ->filter()
            ->values();

        if ($userIds->isEmpty()) {
            return [
                'status' => false,
                'message' => 'Tidak ada customer dengan mitrafk tersebut',
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
                'mitrafk' => $mitraFk,
                'total_user' => $userIds->count(),
                'total_token' => 0,
                'results' => [],
            ];
        }

        $results = [];

        foreach ($tokens as $token) {
            $results[] = $this->sendFcmToToken(
                $token,
                $title,
                $body,
                $data
            );
        }

        return [
            'status' => true,
            'message' => 'FCM dikirim ke customer berdasarkan mitrafk',
            'mitrafk' => $mitraFk,
            'total_user' => $userIds->count(),
            'total_token' => $tokens->count(),
            'results' => $results,
        ];
    }

    private function sendFcmToToken(string $fcmToken, string $title, string $body, array $data = [])
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