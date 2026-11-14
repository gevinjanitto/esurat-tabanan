@extends('layouts.app')
@section('title', 'Daftar Nomor Surat Keluar')
@section('overline', 'Register penomoran')
@section('content')
<form class="card p-4 sm:p-5 mb-6 grid sm:grid-cols-2 lg:grid-cols-[1fr_180px_180px_auto_auto] gap-3 items-end" method="GET" data-reveal data-testid="daftar-nomor-filter">
    <div><label class="label">Cari</label><input name="q" value="{{ request('q') }}" class="input" placeholder="Nomor atau perihal" data-testid="daftar-nomor-search-input"></div>
    <div><label class="label">Dari tanggal</label><input type="date" name="dari" value="{{ request('dari') }}" class="input" data-testid="daftar-nomor-dari-input"></div>
    <div><label class="label">Sampai tanggal</label><input type="date" name="sampai" value="{{ request('sampai') }}" class="input" data-testid="daftar-nomor-sampai-input"></div>
    <button class="btn btn-primary" data-testid="daftar-nomor-filter-button"><i data-lucide="filter" class="w-4 h-4"></i> Tampilkan</button>
    <a href="{{ route('surat-keluar.daftar-nomor.export', request()->only(['q', 'dari', 'sampai'])) }}" class="btn btn-gold" data-no-transition data-testid="daftar-nomor-export-button"><i data-lucide="file-spreadsheet" class="w-4 h-4"></i> Ekspor Excel</a>
</form>
<div class="card overflow-hidden" data-reveal>
    <div class="overflow-x-auto">
        <table class="table-modern" data-testid="daftar-nomor-table">
            <thead><tr><th>Nomor Surat</th><th>Tanggal</th><th>Perihal</th><th>Satker</th><th>Pembuat</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($surats as $s)
                    <tr data-testid="daftar-nomor-row-{{ $s->id }}">
                        <td class="font-mono text-sm font-semibold text-ruby-800 whitespace-nowrap">{{ $s->nomor_surat }}</td>
                        <td class="text-xs text-slate-500 whitespace-nowrap">{{ $s->tanggal_surat?->translatedFormat('d M Y') }}</td>
                        <td class="max-w-sm"><a href="{{ route('surat-keluar.show', $s) }}" class="font-semibold text-maroon-950 hover:text-ruby-700 line-clamp-1">{{ $s->perihal }}</a><p class="text-[11px] text-slate-400 line-clamp-1">{{ $s->tujuan }}</p></td>
                        <td class="text-sm text-slate-600">{{ $s->satker?->nama ?? '—' }}</td>
                        <td class="text-sm text-slate-600 whitespace-nowrap">{{ $s->creator?->name }}</td>
                        <td>@if ($s->is_arsip)<span class="badge badge-gold">Arsip</span>@else<x-badge :color="$s->status_color">{{ $s->status_label }}</x-badge>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-slate-400 py-10">Belum ada surat bernomor pada rentang ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">{{ $surats->links() }}</div>
@endsection
