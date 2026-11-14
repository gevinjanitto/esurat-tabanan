<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public const DEFAULTS = [
        'login_fail_count' => 5,
        'login_fail_lock_minutes' => 15,
        'session_minutes' => 120,
        'sdm_sync_time' => '01:00',
        'satker_penomoran_sekper' => null,
        'satker_penomoran_sisdur' => null,
        'rate_limit_ip' => '0',
        'rate_limit_login' => '1',
        'rate_limit_user' => '0',
    ];

    protected $fillable = ['key', 'value'];

    public static function get(string $key): mixed
    {
        $all = Cache::driver('array')->rememberForever('esurat.settings', fn () => static::query()->pluck('value', 'key')->all());
        return array_key_exists($key, $all) && $all[$key] !== null ? $all[$key] : (self::DEFAULTS[$key] ?? null);
    }

    public static function flag(string $key): bool
    {
        return (bool) (int) static::get($key);
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);
        }
        Cache::driver('array')->forget('esurat.settings');
    }
}
