<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/** Kuota per IP, per login, per pengguna (config/rate_limit.php); on/off di Setting Sistem. */
class RateLimitRequests
{
    public const PREFIX = 'esurat-rl:';

    public function handle(Request $request, Closure $next)
    {
        try {
            $checks = array_filter([
                Setting::flag('rate_limit_ip') ? ['ip:' . $request->ip(), 'ip'] : null,
                Setting::flag('rate_limit_login') && $request->isMethod('POST') && $request->is('login', 'api/m/auth') ? ['login:' . $request->ip(), 'login'] : null,
                Setting::flag('rate_limit_user') && $request->user() ? ['user:' . $request->user()->id, 'user'] : null,
            ]);
        } catch (\Throwable) {
            return $next($request);
        }
        foreach ($checks as [$key, $policy]) {
            $cfg = config("rate_limit.$policy");
            $key = self::PREFIX . $key;
            if (RateLimiter::tooManyAttempts($key, $cfg['requests'])) {
                $retry = RateLimiter::availableIn($key);
                $msg = "Terlalu banyak permintaan. Coba lagi dalam {$retry} detik.";
                return $request->expectsJson()
                    ? response()->json(['message' => $msg], 429, ['Retry-After' => $retry])
                    : ($policy === 'login' ? redirect()->route('login')->withErrors(['username' => $msg])->onlyInput('username') : response($msg, 429, ['Retry-After' => $retry]));
            }
            RateLimiter::hit($key, $cfg['seconds']);
        }
        return $next($request);
    }
}
