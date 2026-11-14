@extends('layouts.public')
@section('title', 'Verifikasi Dokumen')
@section('content')
@if (!$surat || $surat->status !== 2)
    <div class="card max-w-lg mx-auto p-10 text-center mt-10" data-reveal data-testid="verify-invalid">
        <div class="mx-auto grid place-items-center w-20 h-20 rounded-full bg-rose-50 text-rose-500"><i data-lucide="shield-x" class="w-10 h-10"></i></div>
        <h1 class="font-display mt-6 text-2xl font-extrabold text-maroon-950">Dokumen tidak terverifikasi</h1>
        <p class="mt-2 text-sm text-slate-500">QR Code tidak dikenali atau dokumen belum selesai ditandatangani.</p>
    </div>
@else
    <div class="card max-w-2xl mx-auto p-7 sm:p-10 mt-4 overflow-hidden" data-testid="verify-valid">
        <div class="flex flex-col sm:flex-row items-center gap-6">
            <div class="relative shrink-0 w-40">
                <img src="{{ asset('images/woman.webp') }}" alt="Dokumen disetujui" class="w-full animate-floaty drop-shadow-xl">
                <span class="absolute -right-2 bottom-2 grid place-items-center w-14 h-14 rounded-full border-4 border-white bg-ruby-500 text-white animate-stamp"><i data-lucide="shield-check" class="w-7 h-7"></i></span>
            </div>
            <div class="text-center sm:text-left">
                <p class="eyebrow">Dokumen asli</p>
                <h1 class="font-display mt-1 text-3xl font-extrabold text-maroon-950">Tanda tangan sah</h1>
                <p class="mt-1 text-sm text-slate-500">Dokumen ini tercatat dan ditandatangani secara elektronik melalui E-Surat Tabanan.</p>
            </div>
        </div>
        <dl class="mt-8 grid sm:grid-cols-2 gap-3 text-sm">
            @foreach (['Nomor Surat' => $surat->nomor_surat, 'Perihal' => $surat->perihal, 'Tanggal' => optional($surat->tanggal_surat)->translatedFormat('d F Y'), 'Unit' => $surat->satker?->nama ?? '—', 'Ditandatangani' => $surat->signed_at->translatedFormat('d M Y H:i'), 'Kode Dokumen' => $surat->uuid] as $k => $v)
                <div class="rounded-2xl bg-slate-50 p-3.5"><dt class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $k }}</dt><dd class="mt-1 font-semibold text-slate-800 {{ $k === 'Kode Dokumen' ? 'break-all font-mono text-xs' : 'break-words' }}">{{ $v }}</dd></div>
            @endforeach
        </dl>
        <p class="mt-6 eyebrow">Penandatangan</p>
        <div class="mt-3 space-y-2">
            @foreach ($surat->alurs->where('peran', 'penandatangan')->where('status', 1) as $a)
                <div class="flex items-center gap-3 rounded-2xl border border-ruby-100 bg-ruby-50/50 p-3"><i data-lucide="badge-check" class="w-6 h-6 text-ruby-600"></i><div><p class="font-bold text-maroon-950">{{ $a->user->name }}</p><p class="text-xs text-slate-500">{{ $a->user->jabatan?->nama }} · {{ optional($a->acted_at)->translatedFormat('d M Y H:i') }}</p></div></div>
            @endforeach
        </div>
    </div>
@endif
@endsection
