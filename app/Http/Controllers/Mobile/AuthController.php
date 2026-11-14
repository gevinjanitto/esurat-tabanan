<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MobileToken;
use App\Models\User;
use App\Services\PegawaiAuth;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function auth(Request $request, PegawaiAuth $pegawai)
    {
        $d = $request->validate(['username' => 'required|string|max:100', 'password' => 'required|string', 'tipe' => 'nullable|in:pegawai,admin', 'device' => 'nullable|string|max:200']);
        if (($d['tipe'] ?? 'pegawai') === 'pegawai') {
            [$user, $error] = $pegawai->attempt(trim($d['username']), $d['password']);
        } else {
            $user = User::where('username', trim($d['username']))->where('is_active', true)->first();
            $user = $user && Hash::check($d['password'], $user->password) ? $user : null;
            $error = 'Username atau password salah.';
        }
        if (!$user) {
            Activity::log('auth.gagal', 'Login mobile gagal untuk ' . $d['username'], null);
            return response()->json(['status' => false, 'message' => $error], 401);
        }
        [$row, $access, $refresh] = MobileToken::issue($user, $d['device'] ?? $request->userAgent());
        $user->update(['last_login_at' => now()]);
        Activity::log('auth.login', 'Login aplikasi mobile', $user->id);
        return response()->json($this->tokens($row, $access, $refresh) + ['user' => self::user($user)]);
    }

    public function refresh(Request $request)
    {
        $token = $request->bearerToken() ?: $request->input('refresh_token');
        $row = $token ? MobileToken::with('user')->where('refresh_hash', MobileToken::hash($token))->first() : null;
        if (!$row || $row->refresh_expires_at->isPast() || !$row->user?->is_active) {
            return response()->json(['status' => false, 'message' => 'Refresh token tidak valid atau kedaluwarsa.'], 401);
        }
        [$row, $access, $refresh] = MobileToken::issue($row->user, null, $row);
        return response()->json($this->tokens($row, $access, $refresh));
    }

    public function fcm(Request $request)
    {
        $d = $request->validate(['fcm_token' => 'required|string|max:300']);
        MobileToken::where('fcm_token', $d['fcm_token'])->update(['fcm_token' => null]);
        $request->attributes->get('mobile_token')->update(['fcm_token' => $d['fcm_token']]);
        return response()->json(['status' => true, 'message' => 'FCM token disimpan.']);
    }

    public function logout(Request $request)
    {
        $request->attributes->get('mobile_token')->delete();
        Activity::log('auth.logout', 'Logout aplikasi mobile');
        return response()->json(['status' => true, 'message' => 'Berhasil logout.']);
    }

    private function tokens(MobileToken $row, string $access, string $refresh): array
    {
        return [
            'status' => true, 'token_type' => 'Bearer', 'access_token' => $access, 'refresh_token' => $refresh,
            'expires_in' => (int) now()->diffInSeconds($row->access_expires_at), 'refresh_expires_at' => $row->refresh_expires_at->toIso8601String(),
        ];
    }

    public static function user(User $u): array
    {
        $u->loadMissing(['satker', 'jabatan']);
        return ['id' => $u->id, 'nama' => $u->name, 'username' => $u->username, 'nrk' => $u->nrk, 'role' => $u->role, 'role_label' => $u->role_label,
            'disposisi_awal' => (bool) $u->disposisi_awal, 'satker' => $u->satker?->nama, 'jabatan' => $u->jabatan?->nama];
    }
}
