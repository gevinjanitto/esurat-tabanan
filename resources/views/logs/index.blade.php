@extends('layouts.app')
@section('title', 'Catatan Aktivitas')
@section('overline', $isAdmin ? 'Semua pengguna' : 'Aktivitas saya')
@section('content')
<form class="relative max-w-md mb-6" method="GET" data-reveal><i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i><input name="q" value="{{ request('q') }}" class="input pl-11 rounded-full" placeholder="Cari aktivitas…" data-testid="logs-search-input"></form>
<div class="card overflow-hidden" data-reveal>
    <div class="overflow-x-auto">
        <table class="table-modern" data-testid="logs-table">
            <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Deskripsi</th><th>IP</th></tr></thead>
            <tbody>
                @foreach ($logs as $l)
                    <tr><td class="text-xs whitespace-nowrap text-slate-500">{{ $l->created_at->translatedFormat('d M Y H:i:s') }}</td><td class="font-semibold whitespace-nowrap">{{ $l->user?->name ?? '—' }}</td><td><span class="badge badge-slate font-mono">{{ $l->aksi }}</span></td><td class="text-slate-600 min-w-64">{{ $l->deskripsi }}</td><td class="font-mono text-xs text-slate-400">{{ $l->ip }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">{{ $logs->links() }}</div>
@endsection
