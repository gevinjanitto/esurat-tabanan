@extends('layouts.app')
@section('title', 'Status Tindak Lanjut')
@section('overline', $surat->nomor_surat ?: $surat->perihal)
@section('content')
<div class="flex items-center gap-3 mb-6" data-reveal>
    <a href="{{ route('surat-keluar.show', $surat) }}" class="btn-icon btn-ghost" data-testid="tindak-lanjut-back"><i data-lucide="arrow-left" class="w-4 h-4"></i></a>
    <div><h2 class="font-display text-xl font-bold text-maroon-950">{{ $surat->perihal }}</h2><p class="text-xs text-slate-500">{{ $surat->terkirim_at ? 'Terkirim ' . $surat->terkirim_at->translatedFormat('d M Y H:i') : 'Belum terkirim ke penerima internal' }}</p></div>
</div>
@if ($surat->suratMasuks->isEmpty())
    <x-empty title="Belum ada penerima internal" icon="mail-question">Status tindak lanjut tersedia setelah surat selesai ditandatangani dan terkirim ke satker tujuan.</x-empty>
@else
<div class="grid md:grid-cols-2 gap-5" data-reveal>
    @foreach ($surat->suratMasuks as $m)
        @php
            $jenis = $surat->tujuans->firstWhere('satker_id', $m->satker_id)?->jenis ?? 'tujuan';
            $dibaca = $m->teruskans->whereNotNull('dibaca_at')->sortBy('dibaca_at')->first();
            $disp = $m->disposisis->where('is_draft', false);
            $state = $m->status === 'selesai' ? ['Selesai', 'ruby'] : ($disp->isNotEmpty() ? ['Didisposisikan', 'amber'] : ($dibaca ? ['Dibaca', 'sky'] : ['Belum dibaca', 'slate']));
        @endphp
        <article class="card p-6" data-testid="tindak-lanjut-card-{{ $m->id }}">
            <div class="flex items-start justify-between gap-3">
                <div><p class="font-bold text-maroon-950">{{ $m->satker?->nama }}</p><p class="text-[11px] text-slate-500 uppercase tracking-wider font-bold mt-0.5">{{ $jenis === 'tembusan' ? 'Tembusan' : 'Tujuan' }} · {{ $m->nomor_agenda }}</p></div>
                <x-badge :color="$state[1]" data-testid="tindak-lanjut-status-{{ $m->id }}">{{ $state[0] }}</x-badge>
            </div>
            <ol class="relative border-l-2 border-ruby-100 ml-2 mt-5 space-y-3">
                <li class="pl-5 relative text-sm"><span class="absolute -left-[7px] top-1.5 w-3 h-3 rounded-full bg-ruby-500 ring-4 ring-white"></span>Diterima {{ $m->created_at->translatedFormat('d M Y H:i') }}</li>
                <li class="pl-5 relative text-sm"><span class="absolute -left-[7px] top-1.5 w-3 h-3 rounded-full {{ $dibaca ? 'bg-ruby-500' : 'bg-slate-300' }} ring-4 ring-white"></span>{{ $dibaca ? 'Dibaca ' . $dibaca->user?->name . ' · ' . $dibaca->dibaca_at->translatedFormat('d M H:i') : 'Belum dibaca' }}</li>
                @foreach ($disp as $d)
                    <li class="pl-5 relative text-sm"><span class="absolute -left-[7px] top-1.5 w-3 h-3 rounded-full {{ $d->status === 'ditindaklanjuti' ? 'bg-ruby-500' : 'bg-gold-500' }} ring-4 ring-white"></span>{{ $d->dari->name }} → {{ $d->ke->name }}: <span class="text-slate-600">{{ $d->instruksi }}</span> <span class="text-[11px] text-slate-400">({{ $d->status_label }})</span>@if ($d->tindak_lanjut)<span class="block text-xs text-ruby-900 bg-ruby-50 rounded-xl px-2.5 py-1.5 mt-1">{{ $d->tindak_lanjut }}</span>@endif</li>
                @endforeach
            </ol>
        </article>
    @endforeach
</div>
@endif
@endsection
