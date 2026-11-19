<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PegawaiAuth
{
    public const PEGAWAI_ROLES = ['pimpinan', 'sekpri', 'user'];

    public static function ldapEnabled(): bool
    {
        return filled(config('services.ldap.hosts'));
    }

    /** Aturan awalan NRK kkgusnam: "o" dibuang, "k" dicoba utuh lalu tanpa awalan. */
    public function resolve(string $nrk, bool $pegawaiOnly = true): ?User
    {
        $find = fn (string $v) => $v === '' ? null : User::where('is_active', true)
            ->when($pegawaiOnly, fn ($q) => $q->whereIn('role', self::PEGAWAI_ROLES))
            ->where(fn ($w) => $w->where('nrk', $v)->orWhere('username', $v))->first();
        $prefix = strtolower(substr($nrk, 0, 1));
        return match ($prefix) {
            'o' => $find(substr($nrk, 1)),
            'k' => $find($nrk) ?? $find(substr($nrk, 1)),
            default => $find($nrk),
        };
    }

    /** @return array{0: ?User, 1: ?string} */
    public function attempt(string $nrk, string $password): array
    {
        $isPlt = str_starts_with(strtolower($nrk), 'plt');
        $viaLdap = self::ldapEnabled() && !$isPlt;
        if ($viaLdap && !$this->ldapBind($nrk, $password)) {
            return [null, 'NIP atau password salah.'];
        }
        $user = $this->resolve($nrk);
        if (!$user) {
            return [null, 'User tidak terdaftar.'];
        }
        if (!$viaLdap && !Hash::check($password, $user->password)) {
            return [null, 'NIP atau password salah.'];
        }
        return [$user, null];
    }

    public function ldapBind(string $username, string $password): bool
    {
        if (!function_exists('ldap_connect')) {
            Log::error('LDAP: ekstensi php-ldap belum terpasang.');
            return false;
        }
        if ($password === '') {
            return false;
        }
        $principal = $username . '@' . config('services.ldap.domain');
        foreach (array_filter(array_map('trim', explode(',', (string) config('services.ldap.hosts')))) as $url) {
            $conn = @ldap_connect($url);
            if (!$conn) {
                Log::error("LDAP: gagal terhubung ke {$url}");
                continue;
            }
            ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, (int) config('services.ldap.timeout'));
            if (@ldap_bind($conn, $principal, $password)) {
                @ldap_unbind($conn);
                return true;
            }
            $errno = ldap_errno($conn);
            Log::warning("LDAP: bind {$principal} gagal di {$url}: " . ldap_error($conn));
            @ldap_unbind($conn);
            if ($errno === 49) {
                return false;
            }
        }
        return false;
    }
}
