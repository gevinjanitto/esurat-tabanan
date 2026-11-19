<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        abort_unless($request->user() && $request->user()->hasRole(...$roles), 403, 'Anda tidak memiliki akses ke halaman ini.');
        return $next($request);
    }
}
