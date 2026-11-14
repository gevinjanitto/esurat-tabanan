<?php

namespace App\Services;

use App\Models\MobileToken;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PushNotifier
{
    public static function enabled(): bool
    {
        return self::firebaseEnabled() || self::relayEnabled();
    }

    public static function firebaseEnabled(): bool
    {
        $path = config('services.firebase.credentials');
        return filled($path) && is_file(self::path($path));
    }

    /** Relay push aplikasi Android/iOS (layanan notifikasi aplikasi mobile E-Surat). */
    public static function relayEnabled(): bool
    {
        return filled(config('services.push_relay.url')) && filled(config('services.push_relay.secret'));
    }

    private static function path(string $p): string
    {
        return str_starts_with($p, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $p) ? $p : base_path($p);
    }

    public function send(iterable $userIds, string $title, ?string $body, ?string $url = null): int
    {
        $ids = collect($userIds)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return 0;
        }
        $sent = 0;
        if (self::relayEnabled()) {
            $sent += $this->sendRelay($ids->all(), $title, $body, $url);
        }
        if (self::firebaseEnabled()) {
            $sent += $this->sendFcm($ids->all(), $title, $body, $url);
        }
        return $sent;
    }

    private function sendRelay(array $ids, string $title, ?string $body, ?string $url): int
    {
        try {
            $res = Http::timeout(8)->withHeaders(['X-Relay-Secret' => config('services.push_relay.secret')])
                ->post(rtrim(config('services.push_relay.url'), '/') . '/api/push/send', [
                    'user_ids' => array_map('intval', $ids), 'title' => $title, 'message' => (string) $body, 'url' => $url,
                ]);
            return $res->successful() ? (int) $res->json('sent', 0) : 0;
        } catch (\Throwable $e) {
            report($e);
            return 0;
        }
    }

    private function sendFcm(array $ids, string $title, ?string $body, ?string $url): int
    {
        $tokens = MobileToken::whereIn('user_id', $ids)->whereNotNull('fcm_token')->pluck('fcm_token')->unique();
        if ($tokens->isEmpty()) {
            return 0;
        }
        try {
            $cred = json_decode((string) file_get_contents(self::path(config('services.firebase.credentials'))), true);
            $access = $this->accessToken($cred);
            $sent = 0;
            foreach ($tokens as $token) {
                $res = Http::timeout(5)->withToken($access)->post("https://fcm.googleapis.com/v1/projects/{$cred['project_id']}/messages:send", [
                    'message' => ['token' => $token, 'notification' => ['title' => $title, 'body' => (string) $body], 'data' => ['url' => (string) $url]],
                ]);
                if ($res->successful()) {
                    $sent++;
                } elseif (in_array($res->json('error.status'), ['NOT_FOUND', 'UNREGISTERED'], true)) {
                    MobileToken::where('fcm_token', $token)->update(['fcm_token' => null]);
                }
            }
            return $sent;
        } catch (\Throwable $e) {
            report($e);
            return 0;
        }
    }

    private function accessToken(array $cred): string
    {
        return Cache::remember('fcm.access_token', 3000, function () use ($cred) {
            $now = time();
            $jwt = JWT::encode([
                'iss' => $cred['client_email'], 'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $cred['token_uri'] ?? 'https://oauth2.googleapis.com/token', 'iat' => $now, 'exp' => $now + 3600,
            ], $cred['private_key'], 'RS256');
            return Http::asForm()->timeout(8)->post($cred['token_uri'] ?? 'https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt,
            ])->throw()->json('access_token');
        });
    }
}
