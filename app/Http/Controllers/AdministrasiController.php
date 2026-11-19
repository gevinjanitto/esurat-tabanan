<?php

namespace App\Http\Controllers;

use App\Models\CaptchaStat;
use App\Models\Satker;
use App\Models\Sdm;
use App\Models\Setting;
use App\Services\PushNotifier;
use App\Services\SdmSync;
use App\Support\Activity;
use Illuminate\Http\Request;

class AdministrasiController extends Controller
{
    public function sdm(Request $request)
    {
        $q = Sdm::query()->orderBy('nama');
        if ($s = $request->string('q')->trim()->value()) {
            $q->where(fn ($w) => $w->where('nama', 'like', "%$s%")->orWhere('nrk', 'like', "%$s%")->orWhere('unit', 'like', "%$s%"));
        }
        return view('administrasi.sdm', [
            'rows' => $q->paginate(20)->withQueryString(), 'total' => Sdm::count(),
            'lastSync' => Sdm::max('updated_at'), 'configured' => filled(config('services.hairisma.url')), 'jam' => Setting::get('sdm_sync_time'),
        ]);
    }

    public function sync(SdmSync $sync)
    {
        $res = $sync->run();
        Activity::log('sdm.sync', $res['message']);
        if (!$res['ok']) {
            return back()->with('error', $res['message']);
        }
        $st = $res['stats'];
        return back()->with('success', "{$res['message']} {$st['sdm']} pegawai, {$st['user_baru']} user baru, {$st['satker_baru']} satker baru, {$st['jabatan_baru']} jabatan baru.");
    }

    public function setting()
    {
        return view('administrasi.setting', [
            'v' => collect(array_keys(Setting::DEFAULTS))->mapWithKeys(fn ($k) => [$k => Setting::get($k)]),
            'satkers' => Satker::orderBy('nama')->get(),
            'captcha' => CaptchaStat::latest('tanggal')->take(7)->get(),
            'integrasi' => [
                'LDAP pegawai' => filled(config('services.ldap.hosts')), 'SSO OAuth' => SsoController::enabled(),
                'HAIRISMA (SDM)' => filled(config('services.hairisma.url')), 'Firebase push' => PushNotifier::enabled(),
                'Google reCAPTCHA' => filled(config('recaptcha.site_key')),
            ],
        ]);
    }

    public function saveSetting(Request $request)
    {
        $data = $request->validate([
            'login_fail_count' => 'required|integer|between:1,50',
            'login_fail_lock_minutes' => 'required|integer|between:1,1440',
            'session_minutes' => 'required|integer|between:5,1440',
            'sdm_sync_time' => 'required|date_format:H:i',
            'satker_penomoran_sekper' => 'nullable|exists:satkers,id',
            'satker_penomoran_sisdur' => 'nullable|exists:satkers,id',
        ]);
        foreach (['rate_limit_ip', 'rate_limit_login', 'rate_limit_user'] as $k) {
            $data[$k] = $request->boolean($k) ? '1' : '0';
        }
        Setting::put($data);
        Activity::log('setting.ubah', 'Mengubah Setting Sistem');
        return back()->with('success', 'Setting sistem disimpan.');
    }
}
