@extends('layouts.app')
@section('title', $peran === 'penandatangan' ? 'Antrean Tanda Tangan' : 'Antrean Verifikasi')
@section('overline', 'Surat Keluar')
@section('content')
@php $isTtd = $peran === 'penandatangan'; @endphp
<div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6" data-reveal>
    <div class="flex items-center gap-3">
        <span class="grid place-items-center w-12 h-12 rounded-2xl {{ $isTtd ? 'bg-amber-100 text-amber-700' : 'bg-sky-100 text-sky-700' }}"><i data-lucide="{{ $isTtd ? 'signature' : 'scan-search' }}" class="w-6 h-6"></i></span>
        <div><p class="font-display text-2xl font-bold text-maroon-950" data-testid="queue-count">{{ $surats->count() }}</p><p class="text-xs text-slate-500">surat menunggu {{ $isTtd ? 'tanda tangan' : 'verifikasi' }} Anda</p></div>
    </div>
    <form class="relative flex-1 sm:max-w-xs sm:ml-auto" method="GET">
        <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
        <input name="q" value="{{ request('q') }}" class="input pl-11 rounded-full" placeholder="Cari surat…" data-testid="queue-search-input">
    </form>
</div>

@if ($surats->isEmpty())
    <x-empty title="Antrean kosong" icon="{{ $isTtd ? 'signature' : 'scan-search' }}">Tidak ada surat yang menunggu {{ $isTtd ? 'tanda tangan' : 'verifikasi' }} Anda saat ini.</x-empty>
@else
    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4" data-testid="queue-list">
        @foreach ($surats as $s)
            <div class="card card-hover spotlight p-5 flex flex-col" data-reveal data-testid="queue-item-{{ $s->id }}">
                <div class="flex items-center justify-between gap-2">
                    <x-badge :color="$s->queue_mode === 'sekpri' ? 'gold' : $s->status_color">{{ $s->queue_mode === 'sekpri' ? 'Untuk pimpinan' : $s->status_label }}</x-badge>
                    <span class="text-[11px] text-slate-400">{{ $s->updated_at->diffForHumans() }}</span>
                </div>
                <a href="{{ route('surat-keluar.show', $s) }}" class="mt-3 font-display text-[17px] font-bold leading-snug text-maroon-950 hover:text-maroon-700 line-clamp-2">{{ $s->perihal }}</a>
                <p class="text-xs text-slate-500 mt-1">{{ $s->creator->name }} · {{ ucfirst($s->metode_verifikasi) }} @if ($s->revisi)· rev {{ $s->revisi }}@endif</p>
                <div class="mt-4 flex -space-x-2">@foreach ($s->alurs->take(6) as $a)<span title="{{ $a->user->name }} ({{ $a->peran }})" class="grid place-items-center w-8 h-8 rounded-full ring-2 ring-white text-[10px] font-bold {{ $a->status === 1 ? 'bg-ruby-500 text-white' : ($a->status === 2 ? 'bg-rose-500 text-white' : 'bg-slate-200 text-slate-600') }}">{{ $a->user->initials }}</span>@endforeach</div>
                <div class="mt-auto pt-5">
                    @if ($s->queue_mode === 'sekpri')
                        <a href="{{ route('surat-keluar.show', $s) }}" class="btn btn-gold w-full" data-testid="queue-sekpri-open-{{ $s->id }}"><i data-lucide="forward" class="w-4 h-4"></i> Periksa & Teruskan</a>
                    @else
                        <a href="{{ route('surat-keluar.review', $s) }}" class="btn {{ $isTtd ? 'btn-gold' : 'btn-primary' }} w-full" data-testid="queue-review-{{ $s->id }}"><i data-lucide="{{ $isTtd ? 'signature' : 'scan-search' }}" class="w-4 h-4"></i> {{ $isTtd ? 'Tanda Tangan' : 'Verifikasi' }}</a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
