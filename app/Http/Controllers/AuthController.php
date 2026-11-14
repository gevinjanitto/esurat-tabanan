<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Models\CaptchaStat;
use App\Services\PegawaiAuth;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function show(Request $request)
    {
        return view('auth.login', [
            'recaptchaKey' => config('recaptcha.site_key'),
            'ssoEnabled' => SsoController::enabled(),
            'lockMinutes' => $this->lockedFor($request, null),
        ]);
    }

    public function login(Request $request, PegawaiAuth $pegawai)
    {
        $captchaOn = filled(config('recaptcha.site_key')) && filled(config('recaptcha.secret_key'));
        $data = $request->validate([
            'tipe' => 'nullable|in:pegawai,admin',
            'username' => 'required|string|max:100',
            'password' => 'required|string',
            'g-recaptcha-response' => $captchaOn ? 'required|string' : 'nullable',
        ], [
            'g-recaptcha-response.required' => 'Centang captcha "Saya bukan robot" terlebih dahulu.',
            'username.required' => ($request->input('tipe') === 'pegawai' ? 'NIP' : 'Username') . ' wajib diisi.',
        ]);
        $tipe = $data['tipe'] ?? 'admin';
        $username = trim($data['username']);
        $back = fn (string $field, string $msg) => redirect()->route('login')->withErrors([$field => $msg])->withInput($request->only('username', 'tipe'));

        if ($minutes = $this->lockedFor($request, $username)) {
            return $back('username', "Login dikunci karena terlalu banyak percobaan gagal. Coba lagi dalam {$minutes} menit.");
        }
        if ($captchaOn) {
            $human = $this->captchaValid($data['g-recaptcha-response'], $request->ip());
            CaptchaStat::record($human);
            if (!$human) {
                $this->failed($request, $username);
                return $back('g-recaptcha-response', 'Verifikasi captcha gagal, silakan ulangi.');
            }
        }

        [$user, $error] = $tipe === 'pegawai' ? $pegawai->attempt($username, $data['password']) : [$this->adminUser($username, $data['password']), 'Username atau password salah.'];
        if (!$user) {
            $this->failed($request, $username);
            Activity::log('auth.gagal', "Login gagal ({$tipe}) untuk {$username}", null);
            return $back('username', $error);
        }

        Auth::login($user, $request->boolean('remember'));
        $this->started($request, $user, $tipe === 'pegawai' ? 'nrk' : 'form');
        return redirect()->intended(route('dashboard'));
    }

    public function started(Request $request, User $user, string $type): void
    {
        if (config('session.regenerate_on_login')) {
            $request->session()->regenerate();
        }
        $request->session()->forget(['login_fail_count', 'login_lock_until']);
        Cache::forget($this->failKey($user->username));
        $request->session()->put(['login_type' => $type, 'login_expires_at' => now()->addMinutes((int) Setting::get('session_minutes'))->timestamp]);
        $user->update(['last_login_at' => now()]);
        Activity::log('auth.login', 'Login ke aplikasi' . ($type === 'form' ? '' : " ({$type})"), $user->id);
    }

    private function adminUser(string $username, string $password): ?User
    {
        $user = User::where('username', $username)->where('is_active', true)->first();
        return $user && Hash::check($password, $user->password) ? $user : null;
    }

    private function failKey(string $username): string
    {
        return 'login-fail:' . strtolower($username);
    }

    /** Kunci login setelah > N kali gagal selama X menit (login_fail_count / expired_login_fail kkgusnam). */
    private function lockedFor(Request $request, ?string $username): ?int
    {
        $until = max((int) $request->session()->get('login_lock_until'), $username ? (int) (Cache::get($this->failKey($username))['until'] ?? 0) : 0);
        if ($until && now()->timestamp < $until) {
            return (int) ceil(($until - now()->timestamp) / 60);
        }
        if ($request->session()->has('login_lock_until')) {
            $request->session()->forget(['login_fail_count', 'login_lock_until']);
        }
        return null;
    }

    private function failed(Request $request, string $username): void
    {
        $max = (int) Setting::get('login_fail_count');
        $lock = now()->addMinutes((int) Setting::get('login_fail_lock_minutes'))->timestamp;
        $n = (int) $request->session()->get('login_fail_count') + 1;
        $request->session()->put('login_fail_count', $n);
        if ($n > $max) {
            $request->session()->put('login_lock_until', $lock);
        }
        $acc = Cache::get($this->failKey($username), ['n' => 0, 'until' => 0]);
        $acc['n']++;
        if ($acc['n'] > $max) {
            $acc = ['n' => 0, 'until' => $lock];
        }
        Cache::put($this->failKey($username), $acc, now()->addDay());
    }

    public function logout(Request $request)
    {
        if ($request->session()->get('login_type') === 'sso' && SsoController::enabled()) {
            return redirect()->route('sso.logout');
        }
        Activity::log('auth.logout', 'Logout dari aplikasi');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    private function captchaValid(string $token, ?string $ip): bool
    {
        try {
            $res = Http::asForm()->timeout(6)->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => config('recaptcha.secret_key'), 'response' => $token, 'remoteip' => $ip,
            ]);
            return $res->successful() && $res->json('success') === true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }
}
