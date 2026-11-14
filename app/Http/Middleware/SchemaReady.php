<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/** HTTP 503 "sistem sedang disiapkan" bila migrasi belum dijalankan / database belum siap. */
class SchemaReady
{
    private const TABLES = ['users', 'sessions', 'settings', 'mobile_tokens'];

    private static bool $ready = false;

    public function handle(Request $request, Closure $next)
    {
        if (self::$ready || (app()->runningUnitTests() && !config('app.schema_check_in_tests'))) {
            return $next($request);
        }
        try {
            foreach (self::TABLES as $t) {
                if (!Schema::hasTable($t)) {
                    return $this->down('Struktur database belum lengkap. Jalankan: php artisan migrate --seed');
                }
            }
        } catch (\Throwable $e) {
            report($e);
            return $this->down('Database belum bisa dihubungi. Periksa pengaturan DB_* di file .env.');
        }
        self::$ready = true;
        return $next($request);
    }

    private function down(string $hint)
    {
        return response()->view('errors.setup', ['hint' => $hint], 503)->header('Retry-After', '60');
    }
}
