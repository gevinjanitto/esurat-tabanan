@extends('layouts.app')
@section('title', 'Pengguna')
@section('overline', 'Administrasi')
@section('content')
<div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6" data-reveal>
    <form class="flex-1 flex flex-col sm:flex-row gap-2" method="GET">
        <div class="relative flex-1 max-w-md"><i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i><input name="q" value="{{ request('q') }}" class="input pl-11 rounded-full" placeholder="Cari nama, username, NIP…" data-testid="users-search-input"></div>
        <select name="role" class="input rounded-full sm:w-52" onchange="this.form.submit()" data-testid="users-role-filter"><option value="">Semua peran</option>@foreach (\App\Models\User::ROLES as $k => $v)<option value="{{ $k }}" @selected(request('role') === $k)>{{ $v }}</option>@endforeach</select>
    </form>
    <a href="{{ route('users.create') }}" class="btn btn-primary" data-testid="users-create-button"><i data-lucide="user-plus" class="w-4 h-4"></i> Tambah User</a>
</div>
<div class="card overflow-hidden" data-reveal>
    <div class="overflow-x-auto">
        <table class="table-modern" data-testid="users-table">
            <thead><tr><th>Nama</th><th>Username</th><th>Peran</th><th>Jabatan / Satker</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @foreach ($users as $u)
                    <tr data-testid="user-row-{{ $u->id }}">
                        <td><div class="flex items-center gap-3"><span class="grid place-items-center w-9 h-9 rounded-full bg-maroon-900 text-white text-xs font-bold">{{ $u->initials }}</span><div><p class="font-semibold text-maroon-950">{{ $u->name }}</p><p class="text-xs font-mono text-slate-400">{{ $u->nip ?: '—' }}</p></div></div></td>
                        <td class="font-mono text-xs">{{ $u->username }}</td>
                        <td><span class="badge badge-gold">{{ $u->role_label }}</span>@if ($u->disposisi_awal)<span class="badge badge-ruby ml-1" title="Hak disposisi awal">DA</span>@endif</td>
                        <td class="text-xs text-slate-600">{{ $u->jabatan?->nama ?? '—' }}<br><span class="text-slate-400">{{ $u->satker?->nama }}</span></td>
                        <td><x-badge :color="$u->is_active ? 'ruby' : 'slate'">{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge></td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('users.edit', $u) }}" class="btn-icon inline-grid text-slate-500 hover:bg-ruby-50 hover:text-maroon-700" data-testid="user-edit-{{ $u->id }}"><i data-lucide="pencil" class="w-4 h-4"></i></a>
                            @if ($u->is_active && $u->id !== auth()->id())
                                <form method="POST" action="{{ route('users.destroy', $u) }}" class="inline" data-confirm="Nonaktifkan user ini?">@csrf @method('DELETE')<button class="btn-icon text-slate-500 hover:bg-rose-50 hover:text-rose-600" data-testid="user-deactivate-{{ $u->id }}"><i data-lucide="user-x" class="w-4 h-4"></i></button></form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">{{ $users->links() }}</div>
@endsection
