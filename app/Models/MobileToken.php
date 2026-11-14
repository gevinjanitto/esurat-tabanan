<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MobileToken extends Model
{
    protected $fillable = ['user_id', 'access_hash', 'refresh_hash', 'access_expires_at', 'refresh_expires_at', 'fcm_token', 'device', 'last_active_at'];

    protected function casts(): array
    {
        return ['access_expires_at' => 'datetime', 'refresh_expires_at' => 'datetime', 'last_active_at' => 'datetime'];
    }

    public function user() { return $this->belongsTo(User::class); }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    /** @return array{0: self, 1: string, 2: string} */
    public static function issue(User $user, ?string $device = null, ?self $existing = null): array
    {
        $access = Str::random(64);
        $refresh = Str::random(64);
        $attrs = [
            'access_hash' => self::hash($access), 'refresh_hash' => self::hash($refresh),
            'access_expires_at' => now()->addMinutes((int) config('services.mobile.access_minutes')),
            'refresh_expires_at' => now()->addDays((int) config('services.mobile.refresh_days')),
            'last_active_at' => now(),
        ];
        $row = $existing ? tap($existing)->update($attrs) : static::create($attrs + ['user_id' => $user->id, 'device' => $device]);
        return [$row, $access, $refresh];
    }
}
