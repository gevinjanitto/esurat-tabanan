<?php

namespace App\Http\Middleware;

use App\Models\MobileToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobileAuth
{
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');
        $token = $request->bearerToken() ?: $request->query('token');
        $row = $token ? MobileToken::with('user')->where('access_hash', MobileToken::hash($token))->first() : null;
        if (!$row || $row->access_expires_at->isPast() || !$row->user?->is_active) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }
        $row->forceFill(['last_active_at' => now()])->save();
        Auth::setUser($row->user);
        $request->attributes->set('mobile_token', $row);
        return $next($request);
    }
}
