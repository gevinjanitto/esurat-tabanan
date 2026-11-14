<?php

namespace App\Http\Controllers;

use App\Services\PegawaiAuth;
use App\Support\Activity;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/** SSO OAuth2 + PKCE (port Sso_controller kkgusnam). Aktif bila SSO_* diisi di .env. */
class SsoController extends Controller
{
    public static function enabled(): bool
    {
        return filled(config('services.sso.base_url')) && filled(config('services.sso.client_id')) && filled(config('services.sso.client_secret'));
    }

    private function base(): string
    {
        return rtrim((string) config('services.sso.base_url'), '/');
    }

    private function redirectUri(): string
    {
        return config('services.sso.redirect_uri') ?: route('sso.callback');
    }

    public function login(Request $request)
    {
        abort_unless(self::enabled(), 404);
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        $state = bin2hex(random_bytes(16));
        $verifier = bin2hex(random_bytes(32));
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $request->session()->put(['oauth_state' => $state, 'code_verifier' => $verifier]);
        return redirect()->away($this->base() . '/oauth/authorize?' . http_build_query([
            'response_type' => 'code', 'client_id' => config('services.sso.client_id'), 'redirect_uri' => $this->redirectUri(),
            'scope' => 'openid', 'state' => $state, 'code_challenge' => $challenge, 'code_challenge_method' => 'S256',
        ]));
    }

    public function callback(Request $request)
    {
        abort_unless(self::enabled(), 404);
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        abort_unless($request->query('code'), 400, 'Authorization code tidak ditemukan');
        abort_unless(hash_equals((string) $request->session()->get('oauth_state'), (string) $request->query('state')), 400, 'Parameter State tidak valid');
        return view('auth.sso-validate', ['verifyUrl' => route('sso.verify', $request->only('code', 'state'))]);
    }

    public function verify(Request $request, PegawaiAuth $pegawai, AuthController $auth)
    {
        abort_unless(self::enabled(), 404);
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        abort_unless(hash_equals((string) $request->session()->get('oauth_state'), (string) $request->query('state')), 400, 'Parameter State tidak valid');
        $res = Http::asForm()->timeout(30)->post($this->base() . '/oauth/token', [
            'client_id' => config('services.sso.client_id'), 'client_secret' => config('services.sso.client_secret'),
            'redirect_uri' => $this->redirectUri(), 'code' => $request->query('code'), 'grant_type' => 'authorization_code',
            'code_verifier' => $request->session()->get('code_verifier'),
        ]);
        $data = $res->json('data') ?? $res->json() ?? [];
        $code = $res->json('meta.code') ?? $res->status();
        if ($code != 200 || empty($data['id_token'])) {
            logger()->error('[SSO] Gagal mendapatkan access token', ['code' => $code, 'body' => $res->body()]);
            abort(400, $data['message'] ?? 'Gagal mendapatkan access token');
        }
        $payload = $this->decode($data['id_token']);
        $user = $pegawai->resolve((string) ($payload->nrk ?? $payload->sub ?? ''), false);
        if (!$user) {
            return redirect()->route('login')->with('error', 'Akun tidak ditemukan pada database, silakan hubungi administrator.');
        }
        Auth::login($user);
        $auth->started($request, $user, 'sso');
        $request->session()->put(['sso_access_token' => $data['access_token'] ?? null, 'sso_refresh_token' => $data['refresh_token'] ?? null]);
        $request->session()->forget(['oauth_state', 'code_verifier']);
        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        if ($request->session()->get('login_type') !== 'sso') {
            return redirect()->route('login');
        }
        try {
            $token = $this->freshToken($request);
            $meta = Http::timeout(15)->withToken((string) $token)->post($this->base() . '/api/logout')->json('meta.code');
            if ($meta != 200) {
                logger()->warning('[SSO] Logout ke server SSO gagal', ['code' => $meta]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
        Activity::log('auth.logout', 'Logout dari aplikasi (sso)');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function backchannel(Request $request, PegawaiAuth $pegawai)
    {
        $token = (string) $request->input('logout_token');
        abort_unless($token !== '' && self::enabled(), 404);
        try {
            $payload = $this->decode($token);
        } catch (\Throwable $e) {
            logger()->error('[SSO] Backchannel logout token tidak valid: ' . $e->getMessage());
            abort(400, 'Invalid backchannel logout token');
        }
        abort_unless(isset($payload->events->{'http://schemas.openid.net/event/backchannel-logout'}), 400, 'Invalid backchannel logout request');
        $user = $pegawai->resolve((string) ($payload->sub ?? ''), false);
        abort_unless($user, 404);
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
        $user->forceFill(['remember_token' => null])->save();
        Activity::log('auth.logout', 'Logout dari SSO (backchannel)', $user->id);
        return response()->json(['status' => true]);
    }

    private function freshToken(Request $request): ?string
    {
        $token = $request->session()->get('sso_access_token');
        $check = Http::timeout(10)->withToken((string) $token)->get($this->base() . '/api/auth-check')->json('meta.code');
        if ($check == 401 && ($refresh = $request->session()->get('sso_refresh_token'))) {
            $data = Http::asForm()->timeout(15)->post($this->base() . '/oauth/token', [
                'grant_type' => 'refresh_token', 'client_id' => config('services.sso.client_id'),
                'client_secret' => config('services.sso.client_secret'), 'refresh_token' => $refresh,
            ])->json('data') ?? [];
            $token = $data['access_token'] ?? $token;
        }
        return $token;
    }

    private function decode(string $jwt): object
    {
        $jwks = Http::timeout(15)->get(config('services.sso.jwks_url') ?: $this->base() . '/oauth/jwks')->throw()->json();
        JWT::$leeway = 30;
        try {
            return JWT::decode($jwt, JWK::parseKeySet($jwks, 'RS256'));
        } catch (\UnexpectedValueException $e) {
            if (!str_contains($e->getMessage(), '"kid" empty')) {
                throw $e;
            }
            foreach ($jwks['keys'] ?? [] as $k) {
                try {
                    return JWT::decode($jwt, JWK::parseKey($k, 'RS256'));
                } catch (\Throwable) {
                    continue;
                }
            }
            throw $e;
        }
    }
}
