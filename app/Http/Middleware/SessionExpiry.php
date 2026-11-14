<?php

namespace App\Http\Middleware;

use App\Support\Activity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Lama sesi dari Setting Sistem (session_expired kkgusnam), dihitung sejak login. */
class SessionExpiry
{
    public function handle(Request $request, Closure $next)
    {
        $until = $request->hasSession() ? $request->session()->get('login_expires_at') : null;
        if ($until && Auth::check() && now()->timestamp > $until) {
            Activity::log('auth.sesi_habis', 'Sesi berakhir otomatis');
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return $request->expectsJson()
                ? response()->json(['message' => 'Sesi berakhir, silakan masuk kembali.'], 401)
                : redirect()->route('login')->with('error', 'Sesi Anda berakhir, silakan masuk kembali.');
        }
        return $next($request);
    }
}
