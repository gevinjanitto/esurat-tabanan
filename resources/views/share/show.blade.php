@extends('layouts.public')
@section('title', $link->judul)
@push('head') @vite('resources/js/pdf-viewer.js') @endpush
@section('content')
<section class="mb-6" data-reveal>
    <p class="eyebrow">Dokumen dibagikan</p>
    <h1 class="font-display mt-2 text-3xl sm:text-4xl font-extrabold tracking-tight text-maroon-950" data-testid="share-title">{{ $link->judul }}</h1>
    <div class="mt-3 flex flex-wrap gap-2 text-xs">
        <span class="badge badge-ruby"><i data-lucide="user-round" class="w-3 h-3"></i>{{ $link->user->name }}</span>
        <span class="badge {{ $link->canDownload() ? 'badge-gold' : 'badge-slate' }}" data-testid="share-permission-badge"><i data-lucide="{{ $link->canDownload() ? 'download' : 'eye' }}" class="w-3 h-3"></i>{{ $link->canDownload() ? 'Boleh diunduh' : 'Hanya lihat' }}</span>
        <span class="badge badge-sky"><i data-lucide="clock" class="w-3 h-3"></i>{{ $link->expires_at ? 'Aktif s/d ' . $link->expires_at->translatedFormat('d M Y H:i') : 'Tanpa batas waktu' }}</span>
    </div>
</section>
<div class="space-y-6">
    @foreach ($docs as $i => $d)
        @php $doc = $d['doc']; $fileUrl = route('share.file', [$link->token, $d['item']->id]); @endphp
        <section class="card p-3 sm:p-5" data-pdf-shell data-reveal data-testid="share-doc-{{ $i }}">
            <div class="flex flex-wrap items-center justify-between gap-3 px-2 pb-3">
                <div class="min-w-0">
                    <p class="font-bold text-maroon-950">{{ $doc->perihal }}</p>
                    <p class="text-xs font-mono text-slate-400">{{ $doc->nomor_surat ?: 'Belum bernomor' }} · {{ $d['item']->doc_type === 'keluar' ? 'Surat Keluar' : 'Surat Masuk' }}</p>
                </div>
                <div class="flex gap-1.5">
                    <button class="btn-icon btn-ghost" data-zoom-out aria-label="Perkecil"><i data-lucide="zoom-out" class="w-4 h-4"></i></button>
                    <button class="btn-icon btn-ghost" data-zoom-in aria-label="Perbesar"><i data-lucide="zoom-in" class="w-4 h-4"></i></button>
                    @if ($link->canDownload())
                        <a href="{{ $fileUrl }}?download=1" class="btn btn-primary" data-testid="share-download-{{ $i }}"><i data-lucide="download" class="w-4 h-4"></i> Unduh</a>
                    @endif
                </div>
            </div>
            <div class="rounded-2xl bg-slate-100/80 p-2 sm:p-6 max-h-[85vh] overflow-auto scrollbar-thin select-none"><div data-pdf-viewer data-src="{{ $fileUrl }}" @unless ($link->canDownload()) data-no-context @endunless></div></div>
        </section>
    @endforeach
</div>
@endsection
