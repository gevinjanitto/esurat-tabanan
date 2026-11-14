@extends('layouts.app')
@section('title', 'Setting Sistem')
@section('overline', 'Pengaturan')
@section('content')
<div class="grid lg:grid-cols-[1.4fr_1fr] gap-6 items-start">
    <form method="POST" action="{{ route('setting.update') }}" class="card p-6 space-y-6" data-reveal data-testid="setting-form">
        @csrf @method('PUT')
        <div>
            <p class="eyebrow mb-3">Keamanan login</p>
            <div class="grid sm:grid-cols-3 gap-4">
                <div><label class="label">Batas gagal login</label><input type="number" name="login_fail_count" min="1" max="50" value="{{ old('login_fail_count', $v['login_fail_count']) }}" class="input" required data-testid="setting-login-fail-count"><p class="text-[11px] text-slate-400 mt-1">Terkunci jika gagal lebih dari ini</p></div>
                <div><label class="label">Lama kunci (menit)</label><input type="number" name="login_fail_lock_minutes" min="1" max="1440" value="{{ old('login_fail_lock_minutes', $v['login_fail_lock_minutes']) }}" class="input" required data-testid="setting-lock-minutes"></div>
                <div><label class="label">Lama sesi (menit)</label><input type="number" name="session_minutes" min="5" max="1440" value="{{ old('session_minutes', $v['session_minutes']) }}" class="input" required data-testid="setting-session-minutes"></div>
            </div>
        </div>
        <div>
            <p class="eyebrow mb-3">Sinkronisasi &amp; penomoran</p>
            <div class="grid sm:grid-cols-3 gap-4">
                <div><label class="label">Jam sinkron SDM</label><input type="time" name="sdm_sync_time" value="{{ old('sdm_sync_time', substr($v['sdm_sync_time'], 0, 5)) }}" class="input" required data-testid="setting-sdm-sync-time"></div>
                @foreach (['sekper' => 'Satker penomoran Sekper', 'sisdur' => 'Satker penomoran Sisdur'] as $k => $l)
                    <div><label class="label">{{ $l }}</label><select name="satker_penomoran_{{ $k }}" class="input" data-testid="setting-satker-{{ $k }}"><option value="">— satker pembuat —</option>@foreach ($satkers as $s)<option value="{{ $s->id }}" @selected(old("satker_penomoran_$k", $v["satker_penomoran_$k"]) == $s->id)>{{ $s->nama }}</option>@endforeach</select></div>
                @endforeach
            </div>
            <p class="text-[11px] text-slate-400 mt-2">Template surat dengan counter "Sekper" / "Sisdur" memakai nomor urut satker yang dipilih di sini (penomoran terpusat).</p>
        </div>
        <div>
            <p class="eyebrow mb-3">Rate limiting</p>
            <div class="grid sm:grid-cols-3 gap-3">
                @foreach (['rate_limit_ip' => ['Per IP', config('rate_limit.ip')], 'rate_limit_login' => ['Per login', config('rate_limit.login')], 'rate_limit_user' => ['Per pengguna', config('rate_limit.user')]] as $k => [$l, $c])
                    <label class="rounded-2xl border border-slate-200 p-4 flex items-start gap-3 cursor-pointer hover:border-ruby-300 transition-colors">
                        <input type="checkbox" name="{{ $k }}" value="1" class="mt-1 w-4 h-4 accent-ruby-600" @checked((int) $v[$k]) data-testid="setting-{{ str_replace('_', '-', $k) }}">
                        <span><span class="block font-semibold text-maroon-950 text-sm">{{ $l }}</span><span class="text-[11px] text-slate-400">{{ $c['requests'] }} permintaan / {{ $c['seconds'] }} detik</span></span>
                    </label>
                @endforeach
            </div>
        </div>
        <button class="btn btn-primary" data-testid="setting-save-button"><i data-lucide="save" class="w-4 h-4"></i> Simpan Setting</button>
    </form>
    <div class="space-y-6">
        <div class="card p-6" data-reveal data-testid="setting-integrasi">
            <p class="eyebrow mb-3">Status integrasi (.env)</p>
            <ul class="space-y-2 text-sm">
                @foreach ($integrasi as $l => $on)
                    <li class="flex items-center justify-between"><span class="text-slate-600">{{ $l }}</span><span class="badge {{ $on ? 'badge-ruby' : 'badge-slate' }}">{{ $on ? 'Aktif' : 'Tidak aktif' }}</span></li>
                @endforeach
            </ul>
        </div>
        <div class="card overflow-hidden" data-reveal>
            <p class="eyebrow px-6 pt-5">Statistik reCAPTCHA 7 hari</p>
            <table class="table-modern mt-3" data-testid="setting-captcha-table">
                <thead><tr><th>Tanggal</th><th class="text-right">Berhasil</th><th class="text-right">Gagal</th></tr></thead>
                <tbody>
                    @forelse ($captcha as $c)
                        <tr><td>{{ $c->tanggal->translatedFormat('d M Y') }}</td><td class="text-right">{{ $c->success }}</td><td class="text-right text-rose-600">{{ $c->failed }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-slate-400 py-6">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
