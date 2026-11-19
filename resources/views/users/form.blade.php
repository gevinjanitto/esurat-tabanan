@extends('layouts.app')
@section('title', $user->exists ? 'Ubah Pengguna' : 'Tambah Pengguna')
@section('overline', 'Administrasi')
@section('content')
<form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="card max-w-3xl p-6 sm:p-8 space-y-5" data-reveal data-testid="user-form">
    @csrf @if ($user->exists) @method('PUT') @endif
    <div class="grid sm:grid-cols-2 gap-5">
        <div><label class="label">Nama lengkap *</label><input name="name" value="{{ old('name', $user->name) }}" class="input" required data-testid="user-name-input">@error('name')<p class="error-text">{{ $message }}</p>@enderror</div>
        <div><label class="label">Username *</label><input name="username" value="{{ old('username', $user->username) }}" class="input" required data-unique-check="{{ route('users.unique') }}" data-field="username" data-ignore="{{ $user->id }}" data-testid="user-username-input">@error('username')<p class="error-text">{{ $message }}</p>@enderror</div>
        <div><label class="label">Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" class="input" data-testid="user-email-input"></div>
        <div><label class="label">NIP</label><input name="nip" value="{{ old('nip', $user->nip) }}" class="input" data-unique-check="{{ route('users.unique') }}" data-field="nip" data-ignore="{{ $user->id }}" data-testid="user-nip-input">@error('nip')<p class="error-text">{{ $message }}</p>@enderror</div>
        <div><label class="label">Peran *</label><select name="role" class="input" data-testid="user-role-select">@foreach (\App\Models\User::ROLES as $k => $v)@continue($k === 'admin' && !auth()->user()->hasRole('admin'))<option value="{{ $k }}" @selected(old('role', $user->role) === $k)>{{ $v }}</option>@endforeach</select></div>
        <div><label class="label">Satuan kerja</label><select name="satker_id" class="input" data-testid="user-satker-select"><option value="">—</option>@foreach ($satkers as $s)<option value="{{ $s->id }}" @selected(old('satker_id', $user->satker_id) == $s->id)>{{ $s->nama }}</option>@endforeach</select></div>
        <div><label class="label">Jabatan</label><select name="jabatan_id" class="input" data-testid="user-jabatan-select"><option value="">—</option>@foreach ($jabatans as $j)<option value="{{ $j->id }}" @selected(old('jabatan_id', $user->jabatan_id) == $j->id)>{{ $j->nama }}</option>@endforeach</select></div>
        <div><label class="label">Atasan (khusus Sekpri)</label><select name="atasan_id" class="input" data-testid="user-atasan-select"><option value="">—</option>@foreach ($pimpinans as $p)<option value="{{ $p->id }}" @selected(old('atasan_id', $user->atasan_id) == $p->id)>{{ $p->name }}</option>@endforeach</select><p class="text-[11px] text-slate-400 mt-1">Sekpri akan menerima surat & menyiapkan disposisi untuk atasan ini.</p></div>
        <div><label class="label">Password {{ $user->exists ? '(kosongkan bila tetap)' : '*' }}</label><input type="password" name="password" class="input" {{ $user->exists ? '' : 'required' }} autocomplete="new-password" data-testid="user-password-input">@error('password')<p class="error-text">{{ $message }}</p>@enderror</div>
    </div>
    <label class="flex items-start gap-2 text-sm font-semibold text-slate-700"><input type="checkbox" name="disposisi_awal" value="1" class="w-4 h-4 mt-0.5 accent-ruby-600" @checked(old('disposisi_awal', $user->disposisi_awal)) data-testid="user-disposisi-awal-checkbox"><span>Hak disposisi awal<span class="block text-[11px] font-normal text-slate-400">Menerima surat masuk yang diteruskan ke satkernya & melihat submenu Surat Masuk.</span></span></label>
    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700"><input type="checkbox" name="is_active" value="1" class="w-4 h-4 accent-ruby-600" @checked(old('is_active', $user->is_active)) data-testid="user-active-checkbox"> Akun aktif</label>
    <div class="flex gap-2 pt-2"><button class="btn btn-primary" data-testid="user-save-button"><i data-lucide="save" class="w-4 h-4"></i> Simpan</button><a href="{{ route('users.index') }}" class="btn btn-ghost">Batal</a></div>
</form>
@endsection
