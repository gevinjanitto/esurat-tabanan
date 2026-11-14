@extends('layouts.app')
@section('title', 'Agenda')
@section('overline', 'Jadwal rapat & kegiatan untuk semua pengguna')
@section('content')
<div class="flex flex-col lg:flex-row lg:items-center gap-3 mb-6" data-reveal>
    <div class="flex gap-1 rounded-full bg-white border border-slate-200 p-1 w-fit">
        @foreach (['mendatang' => 'Akan datang', 'lalu' => 'Sudah lewat'] as $k => $l)
            <a href="{{ route('agenda.index', ['waktu' => $k]) }}" class="tab {{ $waktu === $k ? 'active' : '' }}" data-testid="agenda-tab-{{ $k }}">{{ $l }}</a>
        @endforeach
    </div>
    <form class="relative flex-1 lg:max-w-xs lg:ml-auto" method="GET"><input type="hidden" name="waktu" value="{{ $waktu }}"><i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i><input name="q" value="{{ request('q') }}" class="input pl-11 rounded-full" placeholder="Cari agenda…" data-testid="agenda-search-input"></form>
    <a href="{{ route('agenda.create') }}" class="btn btn-primary" data-testid="agenda-create-button"><i data-lucide="calendar-plus" class="w-4 h-4"></i> Tambah Agenda</a>
</div>
@if ($agendas->isEmpty())
    <x-empty title="Tidak ada agenda" icon="calendar-days">Tambahkan agenda lewat disposisi, detail surat masuk, atau tombol di atas.</x-empty>
@else
<div class="space-y-3" data-reveal>
    @foreach ($agendas as $a)
        <a href="{{ route('agenda.show', $a) }}" class="card card-hover flex items-center gap-5 p-4 sm:p-5" data-testid="agenda-item-{{ $a->id }}">
            <span class="grid place-items-center shrink-0 w-16 h-16 rounded-3xl {{ $a->mulai->isToday() ? 'bg-gold-500 text-maroon-950' : 'bg-maroon-950 text-white' }} text-center leading-none">
                <span><span class="block font-display text-2xl font-extrabold">{{ $a->mulai->format('d') }}</span><span class="block text-[10px] font-bold uppercase tracking-wider mt-1 opacity-80">{{ $a->mulai->translatedFormat('M Y') }}</span></span>
            </span>
            <span class="min-w-0 flex-1">
                <span class="block font-bold text-maroon-950 truncate">{{ $a->judul }}</span>
                <span class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-xs text-slate-500">
                    <span class="flex items-center gap-1"><i data-lucide="clock-3" class="w-3.5 h-3.5"></i>{{ $a->mulai->format('H:i') }}{{ $a->selesai ? ' – ' . $a->selesai->format('H:i') : '' }} WITA</span>
                    <span class="flex items-center gap-1"><i data-lucide="{{ $a->metode === 'online' ? 'video' : 'map-pin' }}" class="w-3.5 h-3.5"></i>{{ $a->metode === 'online' ? 'Daring' : $a->tempat }}</span>
                    @if ($a->surat)<span class="flex items-center gap-1 truncate"><i data-lucide="inbox" class="w-3.5 h-3.5"></i>{{ $a->surat->nomor_surat }}</span>@endif
                </span>
            </span>
            <span class="badge {{ $a->disposisi_id ? 'badge-gold' : 'badge-slate' }} hidden sm:inline-flex" data-testid="agenda-sumber-badge-{{ $a->id }}">{{ $a->sumber_label }}</span>
            <span class="badge badge-slate hidden sm:inline-flex">{{ \App\Models\Agenda::METODE[$a->metode] }}</span>
        </a>
    @endforeach
</div>
<div class="mt-6">{{ $agendas->links() }}</div>
@endif
@endsection
