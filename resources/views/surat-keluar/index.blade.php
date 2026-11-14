@extends('layouts.app')
@section('title', 'Surat Keluar')
@section('overline', 'Persuratan')
@section('content')
@php $tabs = ['tugas' => ['Perlu Tindakan', 'bell-ring'], 'konsep' => ['Konsep & Revisi', 'file-pen-line'], 'proses' => ['Dalam Proses', 'loader'], 'selesai' => ['Selesai', 'badge-check'], 'semua' => ['Semua', 'layers']]; @endphp
<div class="flex flex-col lg:flex-row lg:items-center gap-3 mb-6" data-reveal>
    <div class="flex gap-1 overflow-x-auto scrollbar-thin rounded-full bg-white border border-slate-200 p-1 -mx-1 sm:mx-0">
        @foreach ($tabs as $k => [$label, $icon])
            <a href="{{ route('surat-keluar.index', ['tab' => $k]) }}" class="tab flex items-center gap-1.5 {{ $tab === $k ? 'active' : '' }}" data-testid="surat-keluar-tab-{{ $k }}"><i data-lucide="{{ $icon }}" class="w-4 h-4"></i>{{ $label }}</a>
        @endforeach
    </div>
    <form class="relative flex-1 lg:max-w-xs lg:ml-auto" method="GET">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
        <input name="q" value="{{ request('q') }}" class="input pl-11 rounded-full" placeholder="Cari surat…" data-testid="surat-keluar-search-input">
        <input type="hidden" name="tag" value="{{ request('tag') }}">
    </form>
    <form method="GET" class="relative lg:w-40"><input type="hidden" name="tab" value="{{ $tab }}"><i data-lucide="hash" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gold-600"></i><input name="tag" value="{{ request('tag') }}" class="input pl-10 rounded-full" placeholder="Tag" data-testid="surat-keluar-tag-filter"></form>
    @unless (auth()->user()->hasRole('admin'))
        <a href="{{ route('surat-keluar.arsip.create') }}" class="btn btn-ghost" data-testid="surat-keluar-arsip-button"><i data-lucide="archive" class="w-4 h-4"></i> Arsip</a>
        <a href="{{ route('surat-keluar.create') }}" class="btn btn-primary" data-magnetic data-testid="surat-keluar-create-button"><i data-lucide="plus" class="w-4 h-4"></i> Buat Surat</a>
    @endunless
</div>

@if ($surats->isEmpty())
    <x-empty title="Tidak ada surat di sini" icon="send">Surat keluar yang Anda buat, verifikasi, atau tandatangani akan tampil pada tab yang sesuai.</x-empty>
@else
    <div class="card overflow-hidden" data-reveal>
        <div class="hidden md:block overflow-x-auto">
            <table class="table-modern" data-testid="surat-keluar-table">
                <thead><tr><th>Perihal</th><th>Pembuat</th><th>Alur</th><th>Status</th><th>Diperbarui</th><th></th></tr></thead>
                <tbody>
                    @foreach ($surats as $s)
                        <tr data-testid="surat-keluar-row-{{ $s->id }}">
                            <td class="max-w-sm"><a href="{{ route('surat-keluar.show', $s) }}" class="font-bold text-maroon-950 hover:text-ruby-700 line-clamp-1">{{ $s->perihal }}</a><p class="text-xs font-mono text-slate-400 mt-0.5">{{ $s->nomor_surat ?: 'Belum bernomor' }} @if ($s->revisi) · rev {{ $s->revisi }} @endif</p></td>
                            <td class="text-slate-600 whitespace-nowrap">{{ $s->creator->name }}</td>
                            <td><div class="flex -space-x-2">@foreach ($s->alurs->take(5) as $a)<span title="{{ $a->user->name }} ({{ $a->peran }})" class="grid place-items-center w-8 h-8 rounded-full ring-2 ring-white text-[10px] font-bold {{ $a->status === 1 ? 'bg-ruby-500 text-white' : ($a->status === 2 ? 'bg-rose-500 text-white' : 'bg-slate-200 text-slate-600') }}">{{ $a->user->initials }}</span>@endforeach</div></td>
                            <td><x-badge :color="$s->status_color">{{ $s->status_label }}</x-badge></td>
                            <td class="text-xs text-slate-500 whitespace-nowrap">{{ $s->updated_at->diffForHumans() }}</td>
                            <td class="text-right whitespace-nowrap">
                                @if ($tab === 'tugas')
                                    <a href="{{ route('surat-keluar.review', $s) }}" class="btn btn-gold !py-2" data-testid="surat-keluar-review-{{ $s->id }}"><i data-lucide="{{ $s->status === 10 ? 'signature' : 'scan-search' }}" class="w-4 h-4"></i>{{ $s->status === 10 ? 'Tanda tangan' : 'Verifikasi' }}</a>
                                @else
                                    <a href="{{ route('surat-keluar.show', $s) }}" class="btn btn-ghost !py-2" data-testid="surat-keluar-open-{{ $s->id }}">Buka</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="md:hidden divide-y divide-slate-100">
            @foreach ($surats as $s)
                <a href="{{ $tab === 'tugas' ? route('surat-keluar.review', $s) : route('surat-keluar.show', $s) }}" class="block p-4 active:bg-ruby-50" data-testid="surat-keluar-mobile-item-{{ $s->id }}">
                    <div class="flex items-center justify-between gap-2"><x-badge :color="$s->status_color">{{ $s->status_label }}</x-badge><span class="text-[11px] text-slate-400">{{ $s->updated_at->diffForHumans() }}</span></div>
                    <p class="mt-2 font-bold text-maroon-950 leading-snug">{{ $s->perihal }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ $s->creator->name }} · {{ $s->nomor_surat ?: 'Belum bernomor' }}</p>
                </a>
            @endforeach
        </div>
    </div>
    <div class="mt-6">{{ $surats->links() }}</div>
@endif
@endsection
