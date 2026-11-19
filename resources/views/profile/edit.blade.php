@extends('layouts.app')
@section('title', 'Profil & Paraf')
@section('overline', 'Akun')
@section('content')
<div class="grid lg:grid-cols-[1fr_1fr] gap-6 max-w-6xl">
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="card p-6 sm:p-8 space-y-5" data-reveal data-testid="profile-form">
        @csrf @method('PUT')
        <div class="flex items-center gap-4">
            <span class="grid place-items-center w-16 h-16 rounded-3xl bg-gradient-to-br from-gold-400 to-gold-600 text-maroon-950 font-display text-xl font-extrabold">{{ $user->initials }}</span>
            <div><p class="font-display text-xl font-bold text-maroon-950">{{ $user->name }}</p><p class="text-sm text-slate-500">{{ $user->role_label }} · {{ $user->jabatan?->nama ?? '—' }}</p><p class="text-xs font-mono text-slate-400">{{ $user->username }}</p></div>
        </div>
        <div><label class="label">Nama</label><input name="name" value="{{ old('name', $user->name) }}" class="input" required data-testid="profile-name-input"></div>
        <div class="grid sm:grid-cols-2 gap-5">
            <div><label class="label">Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" class="input" data-testid="profile-email-input"></div>
            <div><label class="label">NIP</label><input name="nip" value="{{ old('nip', $user->nip) }}" class="input" data-testid="profile-nip-input"></div>
        </div>
        <div data-signature-pad>
            <label class="label">Paraf (untuk verifikasi)</label>
            @if ($user->paraf_path)<div class="mb-3 rounded-2xl border border-slate-200 bg-slate-50 p-3 inline-block"><img src="{{ route('profile.paraf') }}?v={{ $user->updated_at->timestamp }}" alt="Paraf saat ini" class="h-16" data-testid="profile-paraf-current"></div>@endif
            <canvas class="w-full h-40 rounded-2xl border-2 border-dashed border-ruby-200 bg-white touch-none cursor-crosshair" data-testid="profile-paraf-canvas"></canvas>
            <input type="hidden" name="paraf_drawn">
            <div class="flex flex-wrap items-center justify-between gap-2 mt-2">
                <p class="text-xs text-slate-500">Gambar paraf dengan jari, stylus, atau mouse — atau unggah PNG.</p>
                <button type="button" data-clear class="text-xs font-semibold text-rose-600" data-testid="profile-paraf-clear">Bersihkan</button>
            </div>
            <input type="file" name="paraf" accept="image/png,image/jpeg" class="mt-3 text-sm file:mr-3 file:rounded-full file:border-0 file:bg-ruby-50 file:px-4 file:py-2 file:font-semibold file:text-maroon-800" data-testid="profile-paraf-file">
        </div>
        <button class="btn btn-primary" data-testid="profile-save-button"><i data-lucide="save" class="w-4 h-4"></i> Simpan Profil</button>
    </form>

    <form id="password" method="POST" action="{{ route('profile.password') }}" class="card scroll-mt-28 p-6 sm:p-8 space-y-5 self-start" data-reveal data-testid="password-form">
        @csrf @method('PUT')
        <h3 class="font-display text-lg font-bold text-maroon-950">Ubah password</h3>
        <p class="text-xs text-slate-500 -mt-3">Password ini juga dipakai sebagai passphrase TTE.</p>
        <div><label class="label">Password saat ini</label><input type="password" name="current_password" class="input" required data-testid="password-current-input">@error('current_password')<p class="error-text">{{ $message }}</p>@enderror</div>
        <div><label class="label">Password baru</label><input type="password" name="password" class="input" required autocomplete="new-password" data-testid="password-new-input">@error('password')<p class="error-text">{{ $message }}</p>@enderror</div>
        <div><label class="label">Ulangi password baru</label><input type="password" name="password_confirmation" class="input" required data-testid="password-confirm-input"></div>
        <button class="btn btn-gold" data-testid="password-save-button"><i data-lucide="key-round" class="w-4 h-4"></i> Ubah Password</button>
    </form>
</div>
@endsection
