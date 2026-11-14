@extends('layouts.app')
@section('title', 'Detail Agenda')
@section('overline', \App\Models\Agenda::METODE[$agenda->metode])
@section('content')
<div class="grid lg:grid-cols-[1fr_360px] gap-6">
    <section class="card p-6 sm:p-8" data-reveal>
        <h2 class="font-display text-2xl sm:text-3xl font-bold tracking-tight text-maroon-950" data-testid="agenda-judul">{{ $agenda->judul }}</h2>
        <dl class="mt-6 grid sm:grid-cols-2 gap-4 text-sm">
            @foreach (['Tanggal' => $agenda->mulai->translatedFormat('l, d F Y'), 'Waktu' => $agenda->mulai->format('H:i') . ($agenda->selesai ? ' – ' . $agenda->selesai->format('H:i') : '') . ' WITA', 'Tempat' => $agenda->tempat ?: '—', 'Dibuat oleh' => $agenda->user->name, 'Sumber' => $agenda->sumber_label] as $k => $v)
                <div class="rounded-2xl bg-slate-50 p-3.5"><dt class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $k }}</dt><dd class="mt-1 font-semibold text-slate-800">{{ $v }}</dd></div>
            @endforeach
        </dl>
        @if ($agenda->link_meeting)<a href="{{ $agenda->link_meeting }}" target="_blank" rel="noopener" class="btn btn-gold mt-5" data-testid="agenda-link-button"><i data-lucide="video" class="w-4 h-4"></i> Buka tautan rapat</a>@endif
        @if ($agenda->deskripsi)<p class="mt-6 text-sm leading-relaxed text-slate-600 whitespace-pre-line">{{ $agenda->deskripsi }}</p>@endif
        @if ($agenda->user_id === auth()->id())
            <div class="mt-8 flex gap-2">
                <a href="{{ route('agenda.edit', $agenda) }}" class="btn btn-primary" data-testid="agenda-edit-button"><i data-lucide="pencil" class="w-4 h-4"></i> Ubah</a>
                <form method="POST" action="{{ route('agenda.destroy', $agenda) }}" data-confirm="Hapus agenda ini?">@csrf @method('DELETE')<button class="btn btn-danger" data-testid="agenda-delete-button"><i data-lucide="trash-2" class="w-4 h-4"></i> Hapus</button></form>
            </div>
        @endif
    </section>
    <aside class="space-y-6">
        @if ($agenda->surat)
            @php $canSeeSurat = $agenda->surat->isVisibleTo(auth()->user()); @endphp
            <a href="{{ $canSeeSurat ? route('surat-masuk.show', $agenda->surat) : '#' }}" class="card {{ $canSeeSurat ? 'card-hover' : 'pointer-events-none' }} block p-6" data-reveal data-testid="agenda-surat-asal">
                <p class="eyebrow">Surat asal</p>
                <p class="mt-2 font-bold text-maroon-950">{{ $agenda->surat->perihal }}</p>
                <p class="text-xs text-slate-500 mt-1">{{ $agenda->surat->pengirim }} · {{ $agenda->surat->nomor_surat }}</p>
            </a>
        @endif
        @if ($agenda->disposisi)
            <div class="card p-6" data-reveal data-testid="agenda-disposisi-asal">
                <p class="eyebrow">Dari disposisi</p>
                <p class="mt-2 text-sm font-semibold text-maroon-950">{{ $agenda->disposisi->instruksi }}</p>
                <p class="text-xs text-slate-500 mt-1">{{ $agenda->disposisi->dari?->name }} → {{ $agenda->disposisi->ke?->name }}</p>
            </div>
        @endif
        <div class="card p-6 bg-crimson text-white border-0" data-reveal>
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-ruby-300/80">{{ $agenda->mulai->isPast() ? 'Sudah berlangsung' : 'Hitung mundur' }}</p>
            <p class="mt-2 font-display text-3xl font-extrabold">{{ $agenda->mulai->diffForHumans() }}</p>
        </div>
    </aside>
</div>
@endsection
