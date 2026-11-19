@extends('layouts.app')
@section('title', 'Surat Masuk')
@section('overline', 'Persuratan')
@section('content')
@php $canCreate = auth()->user()->hasRole('operator'); @endphp
<div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6" data-reveal>
    <form class="flex-1 flex flex-col sm:flex-row gap-2" method="GET">
        <div class="relative flex-1 max-w-lg">
            <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
            <input name="q" value="{{ request('q') }}" class="input pl-11 rounded-full" placeholder="Cari perihal, nomor, pengirim…" data-testid="surat-masuk-search-input">
        </div>
        <select name="status" class="input rounded-full sm:w-48" onchange="this.form.submit()" data-testid="surat-masuk-status-filter">
            <option value="">Semua status</option>
            @foreach (\App\Models\SuratMasuk::STATUS as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach
        </select>
    </form>
    @if ($canCreate)
        <a href="{{ route('surat-masuk.create') }}" class="btn btn-primary" data-magnetic data-testid="surat-masuk-create-button"><i data-lucide="plus" class="w-4 h-4"></i> Catat Surat Masuk</a>
    @endif
</div>

@if ($surats->isEmpty())
    <x-empty title="Belum ada surat masuk" icon="inbox">Surat masuk yang dicatat atau diteruskan kepada Anda / satker Anda akan muncul di sini.</x-empty>
@else
    <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach ($surats as $s)
            @php $color = ['baru' => 'sky', 'disposisi' => 'amber', 'selesai' => 'ruby'][$s->status] ?? 'slate'; @endphp
            <a href="{{ route('surat-masuk.show', $s) }}" class="card card-hover spotlight p-5 flex flex-col group" data-reveal data-testid="surat-masuk-card-{{ $s->id }}">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-mono text-[11px] text-slate-400">{{ $s->nomor_agenda }}</span>
                    <span class="flex gap-1">@if ($s->jenis === 'internal')<span class="badge badge-ruby">Internal</span>@endif<x-badge :color="$color">{{ $s->status_label }}</x-badge></span>
                </div>
                <h3 class="mt-3 font-display text-[17px] font-bold leading-snug text-maroon-950 line-clamp-2 group-hover:text-maroon-700 transition-colors">{{ $s->perihal }}</h3>
                <p class="mt-1.5 text-sm text-slate-500 truncate"><i data-lucide="building" class="inline w-3.5 h-3.5 -mt-0.5"></i> {{ $s->pengirim }}</p>
                <div class="mt-auto pt-5 flex items-center justify-between text-xs text-slate-500">
                    <span class="flex items-center gap-1.5"><i data-lucide="calendar" class="w-3.5 h-3.5"></i>{{ $s->tanggal_terima->translatedFormat('d M Y') }}</span>
                    <span class="flex items-center gap-3">
                        @if ($s->sifat)<x-badge :color="$s->sifat->warna">{{ $s->sifat->nama }}</x-badge>@endif
                        <span class="flex items-center gap-1" title="Diteruskan"><i data-lucide="forward" class="w-3.5 h-3.5"></i>{{ $s->teruskans->where('status', 'aktif')->count() }}</span>
                        <span class="flex items-center gap-1" title="Disposisi"><i data-lucide="git-branch" class="w-3.5 h-3.5"></i>{{ $s->disposisis->where('is_draft', false)->count() }}</span>
                    </span>
                </div>
            </a>
        @endforeach
    </div>
    <div class="mt-6">{{ $surats->links() }}</div>
@endif
@endsection
