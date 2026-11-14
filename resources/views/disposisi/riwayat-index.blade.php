@extends('layouts.app')
@section('title', 'Riwayat Disposisi')
@section('overline', 'Surat Masuk')
@section('content')
<form class="relative max-w-lg mb-6" method="GET" data-reveal><i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i><input name="q" value="{{ request('q') }}" class="input pl-11 rounded-full" placeholder="Cari perihal / nomor surat…" data-testid="riwayat-search-input"></form>
@if ($surats->isEmpty())
    <x-empty title="Belum ada riwayat disposisi" icon="git-fork">Surat yang memiliki alur disposisi dan melibatkan Anda akan muncul di sini.</x-empty>
@else
    <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach ($surats as $s)
            <a href="{{ route('disposisi.riwayat', $s) }}" class="card card-hover spotlight p-5 flex flex-col group" data-reveal data-testid="riwayat-card-{{ $s->id }}">
                <div class="flex items-center justify-between"><span class="font-mono text-[11px] text-slate-400">{{ $s->nomor_agenda }}</span><x-badge :color="['baru' => 'sky', 'disposisi' => 'amber', 'selesai' => 'ruby'][$s->status] ?? 'slate'">{{ $s->status_label }}</x-badge></div>
                <h3 class="mt-3 font-display text-[17px] font-bold leading-snug text-maroon-950 line-clamp-2 group-hover:text-maroon-700">{{ $s->perihal }}</h3>
                <p class="text-sm text-slate-500 truncate mt-1">{{ $s->pengirim }}</p>
                <div class="mt-auto pt-5 flex items-center justify-between text-xs text-slate-500"><span>{{ $s->tanggal_terima->translatedFormat('d M Y') }}</span><span class="flex items-center gap-1.5 font-semibold text-ruby-700"><i data-lucide="git-fork" class="w-3.5 h-3.5"></i>{{ $s->jumlah_disposisi }} disposisi</span></div>
            </a>
        @endforeach
    </div>
    <div class="mt-6">{{ $surats->links() }}</div>
@endif
@endsection
