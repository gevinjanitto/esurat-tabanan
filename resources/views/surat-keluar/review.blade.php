@extends('layouts.app')
@section('title', $assignment ? ($assignment->peran === 'penandatangan' ? 'Tanda Tangan Surat' : 'Verifikasi Surat') : 'Coretan Revisi')
@section('overline', \Illuminate\Support\Str::limit($surat->perihal, 60))
@push('head') @vite('resources/js/annotator.js') @endpush
@section('content')
@php
    $editable = (bool) $assignment;
    $isVerifier = $assignment?->peran === 'verifikator';
    $parafUrl = $isVerifier && $user->paraf_path ? route('profile.paraf') : '';
    $basah = $surat->metode_ttd === 'basah';
@endphp
<div id="studio" class="grid xl:grid-cols-[1fr_360px] gap-6"
     data-src="{{ route('surat-keluar.file', $surat) }}" data-load-url="{{ route('surat-keluar.annotations', $surat) }}" data-save-url="{{ route('surat-keluar.annotations.save', $surat) }}"
     data-editable="{{ $editable ? 1 : 0 }}" data-can-paraf="{{ $isVerifier ? 1 : 0 }}" data-paraf-url="{{ $parafUrl }}" data-user-name="{{ $user->name }}" data-testid="annotation-studio">
    <div class="min-w-0">
        @if ($editable)
            <div class="sticky top-[5.25rem] lg:top-28 z-20 mb-4 flex justify-center" data-reveal>
                <div class="flex items-center gap-1 overflow-x-auto scrollbar-thin max-w-full rounded-full bg-white/90 backdrop-blur-xl border border-ruby-900/10 shadow-xl shadow-ruby-950/10 p-1.5" role="toolbar" aria-label="Alat anotasi" data-testid="annotation-toolbar">
                    @foreach (['hand' => ['hand', 'Geser'], 'pen' => ['pen-line', 'Pena'], 'highlighter' => ['highlighter', 'Stabilo'], 'eraser' => ['eraser', 'Penghapus'], 'note' => ['message-square-plus', 'Catatan']] + ($isVerifier ? ['paraf' => ['stamp', 'Paraf']] : []) as $t => [$icon, $label])
                        <button type="button" data-tool="{{ $t }}" title="{{ $label }}" class="flex items-center gap-1.5 rounded-full px-3 py-2 text-xs font-bold text-slate-600 hover:bg-ruby-50 transition-colors" data-testid="pdf-annotation-{{ $t }}-tool"><i data-lucide="{{ $icon }}" class="w-4 h-4"></i><span class="hidden md:inline">{{ $label }}</span></button>
                    @endforeach
                    <span class="w-px h-6 bg-slate-200 mx-1"></span>
                    @foreach (['#e11d48', '#2563eb', '#0f766e', '#111827'] as $i => $c)
                        <button type="button" data-color="{{ $c }}" class="w-6 h-6 shrink-0 rounded-full ring-offset-2 ring-ruby-500 {{ $i === 0 ? 'ring-2' : '' }}" style="background: {{ $c }}" aria-label="Warna {{ $c }}" data-testid="pdf-annotation-color-{{ $i }}"></button>
                    @endforeach
                    <input type="range" min="1" max="10" value="3" data-size class="w-16 mx-1 accent-ruby-600" aria-label="Ketebalan" data-testid="pdf-annotation-size">
                    <span class="w-px h-6 bg-slate-200 mx-1"></span>
                    <button type="button" data-undo class="btn-icon text-slate-500 hover:bg-slate-100" title="Urungkan" data-testid="pdf-annotation-undo"><i data-lucide="undo-2" class="w-4 h-4"></i></button>
                    <button type="button" data-clear class="btn-icon text-slate-500 hover:bg-rose-50 hover:text-rose-600" title="Hapus semua milik saya" data-testid="pdf-annotation-clear"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    <button type="button" data-save class="btn btn-primary !py-2 !px-4 !text-xs" data-testid="pdf-annotation-save-button"><i data-lucide="save" class="w-4 h-4"></i>Simpan</button>
                </div>
            </div>
        @endif
        <div class="rounded-[2rem] bg-[#efe4e6] p-2 sm:p-6 min-h-[60vh]" data-reveal>
            <div class="flex items-center justify-between px-2 pb-3 text-xs font-semibold text-slate-500">
                <span data-page-count>Memuat…</span>
                <span data-save-status data-testid="annotation-save-status">{{ $editable ? 'Coret, beri catatan, lalu putuskan' : 'Mode baca' }}</span>
            </div>
            <div data-studio-pages></div>
        </div>
    </div>

    <aside class="space-y-6 xl:sticky xl:top-28 xl:self-start">
        @if ($editable)
            <section class="card p-6" data-reveal data-testid="decision-panel">
                <p class="eyebrow">Keputusan Anda</p>
                <h3 class="font-display text-lg font-bold text-maroon-950 mt-1">{{ $isVerifier ? 'Verifikasi surat' : ($basah ? 'Tanda tangan basah' : 'Tanda tangan elektronik') }}</h3>
                <p class="text-xs text-slate-500 mt-1 mb-4">{{ $isVerifier ? 'Bubuhkan paraf (opsional) lalu setujui, atau minta revisi dengan catatan & coretan.' : ($basah ? 'Unduh & cetak surat, tanda tangani basah, lalu unggah hasil scan PDF-nya.' : 'Masukkan passphrase TTE untuk menandatangani, atau kembalikan untuk revisi.') }}</p>
                <form method="POST" action="{{ route('surat-keluar.decide', $surat) }}" class="space-y-3" enctype="multipart/form-data" data-decision-form>@csrf
                    <textarea name="catatan" rows="3" class="input" placeholder="Catatan umum (wajib jika minta revisi)…" data-testid="decision-catatan-input">{{ old('catatan') }}</textarea>
                    @error('catatan')<p class="error-text">{{ $message }}</p>@enderror
                    @if (!$isVerifier && $basah)
                        <a href="{{ route('surat-keluar.file', [$surat, 'download' => 1]) }}" class="btn btn-soft w-full" data-testid="decision-download-print-button"><i data-lucide="printer" class="w-4 h-4"></i> Unduh untuk dicetak</a>
                        <label class="label !mb-1">Scan surat bertanda tangan basah (PDF) *</label>
                        <input type="file" name="scan_ttd" accept="application/pdf" class="input file:mr-3 file:rounded-full file:border-0 file:bg-ruby-50 file:px-4 file:py-1.5 file:text-ruby-800 file:font-semibold" data-testid="decision-scan-ttd-input">
                        @error('scan_ttd')<p class="error-text" data-testid="decision-scan-ttd-error">{{ $message }}</p>@enderror
                    @elseif (!$isVerifier)
                        <div class="relative"><i data-lucide="key-round" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-ruby-700/60"></i><input type="password" name="passphrase" class="input pl-11" placeholder="Passphrase TTE" autocomplete="current-password" data-testid="decision-passphrase-input"></div>
                        @error('passphrase')<p class="error-text" data-testid="decision-passphrase-error">{{ $message }}</p>@enderror
                    @endif
                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <button name="action" value="revisi" class="btn btn-danger" data-testid="decision-revisi-button"><i data-lucide="undo-2" class="w-4 h-4"></i> Revisi</button>
                        @if ($isVerifier)
                            <button name="action" value="setuju" class="btn btn-primary" data-testid="decision-setuju-button"><i data-lucide="check" class="w-4 h-4"></i> Setujui</button>
                        @else
                            <button name="action" value="tandatangan" class="btn btn-gold" data-testid="decision-tandatangan-button"><i data-lucide="signature" class="w-4 h-4"></i> Tanda Tangan</button>
                        @endif
                    </div>
                </form>
                @if ($isVerifier)
                    <a href="{{ route('surat-keluar.show', [$surat, 'teruskan' => 1]) }}#teruskan" class="btn btn-soft w-full mt-2" data-testid="decision-teruskan-link"><i data-lucide="forward" class="w-4 h-4"></i> Setujui & Teruskan ke Pejabat Lain</a>
                @endif
                @if ($isVerifier && !$user->paraf_path)
                    <p class="mt-4 text-[11px] text-slate-500 rounded-xl bg-slate-50 p-3">Belum ada gambar paraf. Paraf akan tampil sebagai inisial. <a href="{{ route('profile.edit') }}" class="font-semibold text-ruby-700 underline">Atur paraf</a></p>
                @endif
            </section>
        @else
            <section class="card p-6" data-reveal>
                <p class="eyebrow">Mode baca</p>
                <p class="text-sm text-slate-600 mt-2">Anda melihat coretan & catatan dari verifikator/penandatangan pada revisi ini.</p>
                <a href="{{ route('surat-keluar.show', $surat) }}" class="btn btn-ghost w-full mt-4" data-testid="review-back-button"><i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali ke detail</a>
            </section>
        @endif
        <section class="card p-6" data-reveal>
            <p class="eyebrow">Catatan</p>
            <div class="mt-3 space-y-2 max-h-[40vh] overflow-y-auto scrollbar-thin" data-notes-list data-testid="notes-list"></div>
            <p class="mt-4 text-[11px] text-slate-400 flex items-center gap-1.5"><i data-lucide="smartphone" class="w-3.5 h-3.5"></i> Mendukung mouse, layar sentuh & stylus.</p>
        </section>
    </aside>
</div>
@endsection

@push('modals')
<div id="note-modal" class="modal-backdrop" data-testid="note-modal">
    <div class="modal-panel">
        <p class="eyebrow">Catatan revisi</p>
        <h3 class="font-display text-xl font-bold text-maroon-950 mt-1 mb-4">Tulis catatan untuk bagian ini</h3>
        <textarea rows="4" maxlength="1000" class="input" placeholder="mis. Perbaiki penulisan nama jabatan…" data-testid="note-modal-input"></textarea>
        <div class="grid grid-cols-2 gap-2 mt-4">
            <button type="button" class="btn btn-ghost" data-note-cancel data-testid="note-modal-cancel">Batal</button>
            <button type="button" class="btn btn-primary" data-note-ok data-testid="note-modal-save"><i data-lucide="check" class="w-4 h-4"></i> Simpan Catatan</button>
        </div>
    </div>
</div>
@endpush
