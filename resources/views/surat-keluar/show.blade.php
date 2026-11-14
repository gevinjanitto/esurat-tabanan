@extends('layouts.app')
@section('title', 'Detail Surat Keluar')
@section('overline', $surat->nomor_surat ?: 'Belum bernomor')
@push('head') @vite('resources/js/pdf-viewer.js') @endpush
@section('content')
@php
    $me = auth()->user();
    $isOwner = $surat->created_by === $me->id;
    $aksiLabel = ['dibuat' => ['Konsep dibuat', 'file-plus-2', 'slate'], 'diajukan' => ['Diajukan', 'send', 'sky'], 'diajukan_ulang' => ['Diajukan ulang (revisi)', 'refresh-ccw', 'sky'], 'disetujui' => ['Disetujui', 'check', 'ruby'], 'revisi' => ['Minta revisi', 'undo-2', 'rose'], 'ditandatangani' => [$surat->metode_ttd === 'basah' ? 'Ditandatangani (basah)' : 'Ditandatangani (TTE)', 'signature', 'amber'], 'diteruskan' => ['Disetujui & diteruskan', 'forward', 'ruby'], 'diteruskan_sekpri' => ['Diteruskan Sekpri ke pimpinan', 'user-round-check', 'sky'], 'batal_kirim' => ['Pengiriman dibatalkan', 'circle-x', 'slate'], 'unggah_ulang' => ['File surat diunggah ulang', 'file-up', 'slate'], 'terkirim' => ['Terkirim ke penerima internal', 'mail-check', 'ruby'], 'arsip' => ['Diinput sebagai arsip', 'archive', 'slate']];
    $canManage = $isOwner && $surat->isEditable();
@endphp
<div class="grid xl:grid-cols-[1fr_400px] gap-6">
    <div class="space-y-6 min-w-0">
        <section class="card p-6 sm:p-8 overflow-hidden" data-reveal>
            @if ($surat->status === 2)
                <div class="absolute right-6 top-6 hidden sm:grid place-items-center w-32 h-32 rounded-full border-4 border-ruby-500/70 text-ruby-600 font-display font-extrabold text-center text-[10px] tracking-tight leading-tight px-2 animate-stamp" data-testid="signed-stamp"><span><i data-lucide="badge-check" class="w-7 h-7 mx-auto"></i>TELAH<br>DITANDATANGANI</span></div>
            @endif
            <div class="flex flex-wrap gap-2 mb-3">
                <x-badge :color="$surat->status_color" data-testid="surat-keluar-status">{{ $surat->status_label }}</x-badge>
                <span class="badge badge-slate">{{ ucfirst($surat->metode_verifikasi) }}</span>
                <span class="badge badge-gold" data-testid="surat-keluar-metode-ttd-badge"><i data-lucide="{{ $surat->metode_ttd === 'basah' ? 'pen-tool' : 'signature' }}" class="w-3 h-3"></i>{{ \App\Models\SuratKeluar::METODE_TTD[$surat->metode_ttd] ?? 'Tandatangan Elektronik' }}</span>
                @if ($surat->revisi)<span class="badge badge-gold">Revisi ke-{{ $surat->revisi }}</span>@endif
                @if ($surat->terkirim_at)<span class="badge badge-ruby" data-testid="surat-keluar-terkirim-badge"><i data-lucide="mail-check" class="w-3 h-3"></i> Terkirim {{ $surat->terkirim_at->translatedFormat('d M H:i') }}</span>@endif
                @if ($surat->sifat)<x-badge :color="$surat->sifat->warna">{{ $surat->sifat->nama }}</x-badge>@endif
                @if ($surat->is_arsip)<span class="badge badge-gold" data-testid="surat-keluar-arsip-badge"><i data-lucide="archive" class="w-3 h-3"></i> Arsip</span>@endif
                @if ($surat->templateSurat)<span class="badge badge-slate" data-testid="surat-keluar-template-badge"><i data-lucide="file-type-2" class="w-3 h-3"></i> {{ $surat->templateSurat->nama }}</span>@endif
            </div>
            <h2 class="font-display text-2xl sm:text-3xl font-bold tracking-tight text-maroon-950 sm:pr-32" data-testid="surat-keluar-perihal">{{ $surat->perihal }}</h2>
            <p class="text-slate-500 mt-1">Kepada: {{ $surat->tujuan }}</p>
            @if ($surat->tujuans->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-1.5" data-testid="surat-keluar-tujuan-internal">
                    @foreach ($surat->tujuans as $t)<span class="badge {{ $t->jenis === 'tujuan' ? 'badge-ruby' : 'badge-slate' }}"><i data-lucide="{{ $t->jenis === 'tujuan' ? 'building-2' : 'copy' }}" class="w-3 h-3"></i>{{ $t->jenis === 'tembusan' ? 'Tembusan: ' : '' }}{{ $t->satker->nama }}</span>@endforeach
                </div>
            @endif
            <dl class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                @foreach (['Nomor' => $surat->nomor_surat ?: '—', 'Tanggal' => optional($surat->tanggal_surat)->translatedFormat('d M Y') ?? '—', 'Pembuat' => $surat->creator->name, 'Unit' => $surat->satker?->nama ?? '—'] as $k => $v)
                    <div class="rounded-2xl bg-slate-50 p-3.5"><dt class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $k }}</dt><dd class="mt-1 font-semibold text-slate-800 break-words">{{ $v }}</dd></div>
                @endforeach
            </dl>
            <div class="mt-6 flex flex-wrap gap-2">
                @if ($assignment)
                    <a href="{{ route('surat-keluar.review', $surat) }}" class="btn btn-gold" data-magnetic data-testid="surat-keluar-review-button"><i data-lucide="{{ $assignment->peran === 'penandatangan' ? 'signature' : 'scan-search' }}" class="w-4 h-4"></i>{{ $assignment->peran === 'penandatangan' ? 'Tinjau & Tanda Tangan' : 'Tinjau & Verifikasi' }}</a>
                @endif
                @if ($isOwner && $surat->isEditable())
                    <a href="{{ route('surat-keluar.edit', $surat) }}" class="btn btn-primary" data-testid="surat-keluar-edit-button"><i data-lucide="file-pen-line" class="w-4 h-4"></i>{{ $surat->status === 0 ? 'Ubah Konsep' : 'Revisi Surat' }}</a>
                    <form method="POST" action="{{ route('surat-keluar.submit', $surat) }}">@csrf<button class="btn btn-soft" data-testid="surat-keluar-ajukan-button"><i data-lucide="send" class="w-4 h-4"></i> Ajukan{{ $surat->status ? ' Ulang' : '' }}</button></form>
                @endif
                @if (in_array($surat->status, [11, 12]))
                    <a href="{{ route('surat-keluar.review', $surat) }}" class="btn btn-ghost" data-testid="surat-keluar-lihat-coretan"><i data-lucide="pen-tool" class="w-4 h-4"></i> Lihat Coretan Revisi</a>
                @endif
                @if ($surat->terkirim_at)
                    <a href="{{ route('surat-keluar.tindak-lanjut', $surat) }}" class="btn btn-soft" data-testid="surat-keluar-tindak-lanjut-button"><i data-lucide="list-checks" class="w-4 h-4"></i> Status Tindak Lanjut</a>
                @endif
                @if ($isOwner && $surat->isWithdrawable())
                    <form method="POST" action="{{ route('surat-keluar.batal-kirim', $surat) }}" data-confirm="Tarik kembali surat ini? Surat akan kembali menjadi konsep.">@csrf<button class="btn btn-ghost" data-testid="surat-keluar-batal-kirim-button"><i data-lucide="undo-2" class="w-4 h-4"></i> Batal Kirim</button></form>
                @endif
                @if ($canManage)
                    <button type="button" class="btn btn-ghost" data-modal-open="reupload-modal" data-testid="surat-keluar-reupload-button"><i data-lucide="file-up" class="w-4 h-4"></i> Unggah Ulang File</button>
                @endif
                @if ($isOwner && $surat->status === 0)
                    <form method="POST" action="{{ route('surat-keluar.destroy', $surat) }}" data-confirm="Hapus konsep ini?">@csrf @method('DELETE')<button class="btn btn-danger" data-testid="surat-keluar-delete-button"><i data-lucide="trash-2" class="w-4 h-4"></i></button></form>
                @endif
            </div>
        </section>

        @if ($surat->currentFile())
            <section class="card p-3 sm:p-5" data-pdf-shell data-reveal>
                <div class="flex items-center justify-between px-2 pb-3 gap-2">
                    <p class="font-bold text-maroon-950 flex items-center gap-2"><i data-lucide="file-text" class="w-4 h-4 text-ruby-600"></i>{{ $surat->signed_path ? 'Dokumen bertanda tangan' : 'Konsep dokumen' }}</p>
                    <div class="flex gap-1.5">
                        <button class="btn-icon btn-ghost" data-zoom-out aria-label="Perkecil" data-testid="pdf-zoom-out"><i data-lucide="zoom-out" class="w-4 h-4"></i></button>
                        <button class="btn-icon btn-ghost" data-zoom-in aria-label="Perbesar" data-testid="pdf-zoom-in"><i data-lucide="zoom-in" class="w-4 h-4"></i></button>
                        <a href="{{ route('surat-keluar.file', [$surat, 'download' => 1]) }}" class="btn btn-soft" data-testid="surat-keluar-download-button"><i data-lucide="download" class="w-4 h-4"></i><span class="hidden sm:inline">Unduh</span></a>
                    </div>
                </div>
                <div class="rounded-2xl bg-slate-100/80 p-2 sm:p-6 max-h-[80vh] overflow-auto scrollbar-thin"><div data-pdf-viewer data-src="{{ route('surat-keluar.file', $surat) }}"></div></div>
            </section>
        @endif
    </div>

    <aside class="space-y-6">
        @if ($sekpriRow)
            <section class="card p-6 border-gold-400/40 bg-gradient-to-br from-[#fffbeb] to-white" data-reveal data-testid="sekpri-forward-card">
                <p class="eyebrow !text-gold-600">Sekpri</p>
                <p class="mt-2 font-bold text-maroon-950">Surat menunggu diteruskan ke {{ $sekpriRow->user->name }}</p>
                <p class="text-xs text-slate-500 mt-1">Periksa kelengkapan lalu teruskan ke pimpinan untuk {{ $sekpriRow->peran === 'penandatangan' ? 'ditandatangani' : 'diverifikasi' }}.</p>
                <form method="POST" action="{{ route('surat-keluar.teruskan-sekpri', $surat) }}" class="mt-4 space-y-3">@csrf
                    <textarea name="catatan" rows="2" class="input" placeholder="Catatan untuk pimpinan (opsional)" data-testid="sekpri-forward-catatan"></textarea>
                    <button class="btn btn-gold w-full" data-testid="sekpri-forward-button"><i data-lucide="forward" class="w-4 h-4"></i> Teruskan ke Pimpinan</button>
                </form>
            </section>
        @endif
        @if ($forwardUsers->isNotEmpty())
            <section id="teruskan" class="card p-6 scroll-mt-28 {{ request('teruskan') ? 'ring-2 ring-gold-400' : '' }}" data-reveal data-testid="verifikator-forward-card">
                <p class="eyebrow">Teruskan</p>
                <h3 class="font-display text-lg font-bold text-maroon-950 mt-1">Setujui & teruskan ke pejabat lain</h3>
                <p class="text-xs text-slate-500 mt-1 mb-4">Pejabat yang dipilih ditambahkan sebagai verifikator berikutnya setelah Anda.</p>
                <form method="POST" action="{{ route('surat-keluar.teruskan', $surat) }}" class="space-y-3">@csrf
                    <select name="user_id" class="input" required data-testid="verifikator-forward-user-select"><option value="">Pilih pejabat…</option>@foreach ($forwardUsers as $fu)<option value="{{ $fu->id }}">{{ $fu->name }} — {{ $fu->jabatan?->nama ?? $fu->role_label }}{{ $fu->hasRole('sekpri') ? ' (Sekpri)' : '' }}</option>@endforeach</select>
                    <textarea name="catatan" rows="2" class="input" placeholder="Catatan (opsional)" data-testid="verifikator-forward-catatan"></textarea>
                    <button class="btn btn-primary w-full" data-testid="verifikator-forward-submit"><i data-lucide="forward" class="w-4 h-4"></i> Setujui & Teruskan</button>
                </form>
            </section>
        @endif
        <section class="card p-6" data-reveal data-testid="surat-keluar-tags-panel"><p class="eyebrow mb-3">Tag saya</p>@include('partials.tags', ['type' => 'surat_keluar', 'id' => $surat->id, 'tags' => $tags])</section>
        <section class="card p-6" data-reveal data-testid="alur-panel">
            <p class="eyebrow">Alur</p>
            <h3 class="font-display text-lg font-bold text-maroon-950 mt-1 mb-4">Verifikasi & tanda tangan</h3>
            <div class="space-y-2.5">
                @foreach ($surat->alurs as $a)
                    @php $current = in_array($surat->status, [1, 10]) && $a->status === 0 && (($surat->status === 1 && $a->peran === 'verifikator') || ($surat->status === 10 && $a->peran === 'penandatangan')); @endphp
                    <div class="flex items-start gap-3 rounded-2xl border p-3 {{ $current ? 'border-gold-400 bg-[#fffbeb]' : 'border-slate-100' }}" data-testid="alur-row-{{ $a->id }}">
                        <span class="grid place-items-center w-10 h-10 shrink-0 rounded-2xl text-xs font-bold {{ $a->status === 1 ? 'bg-ruby-500 text-white' : ($a->status === 2 ? 'bg-rose-500 text-white' : 'bg-slate-100 text-slate-600') }}">
                            @if ($a->status === 1)<i data-lucide="check" class="w-5 h-5"></i>@elseif ($a->status === 2)<i data-lucide="undo-2" class="w-5 h-5"></i>@else{{ $a->user->initials }}@endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-slate-800 truncate">{{ $a->user->name }}</p>
                            <p class="text-[11px] text-slate-500">{{ $a->peran === 'penandatangan' ? 'Penandatangan' : 'Verifikator' }} #{{ $a->urut }} · {{ $a->status === 0 && $a->status_sekpri === 1 ? 'Menunggu Sekpri' : \App\Models\SuratKeluarAlur::STATUS[$a->status] }}@if ($a->diteruskan_dari_id) · <span class="text-gold-600 font-semibold">diteruskan</span>@endif</p>
                            @if ($a->catatan)<p class="mt-1.5 text-xs rounded-xl bg-rose-50 text-rose-800 px-2.5 py-1.5">“{{ $a->catatan }}”</p>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        @if ($notes->isNotEmpty())
            <section class="card p-6" data-reveal data-testid="revision-notes-panel">
                <p class="eyebrow !text-gold-600">Catatan pada dokumen</p>
                <div class="mt-3 space-y-2">
                    @foreach ($notes as $n)
                        <div class="rounded-2xl bg-slate-50 px-3.5 py-2.5"><p class="text-xs font-bold text-maroon-800">{{ $n['author'] }} <span class="font-mono font-normal text-slate-400">· hal {{ $n['page'] }}</span></p><p class="text-sm text-slate-700">{{ $n['text'] }}</p></div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="card p-6" data-reveal data-testid="lampiran-panel">
            <p class="eyebrow">Lampiran</p>
            <div class="mt-3 space-y-2">
                @forelse ($surat->lampirans as $l)
                    <div class="flex items-center gap-2 rounded-2xl bg-slate-50 px-3 py-2" data-testid="lampiran-item-{{ $l->id }}">
                        <i data-lucide="paperclip" class="w-4 h-4 text-ruby-600 shrink-0"></i>
                        <a href="{{ route('surat-keluar.lampiran.download', $l) }}" class="text-sm font-semibold text-slate-700 hover:text-ruby-700 truncate flex-1" data-no-transition data-testid="lampiran-download-{{ $l->id }}">{{ $l->file_name }}</a>
                        <span class="text-[10px] text-slate-400">{{ number_format($l->size / 1024, 0) }} KB</span>
                        @if ($canManage)
                            <form method="POST" action="{{ route('surat-keluar.lampiran.destroy', $l) }}" data-confirm="Hapus lampiran ini?">@csrf @method('DELETE')<button class="btn-icon !p-1.5 text-slate-400 hover:text-rose-600" data-testid="lampiran-delete-{{ $l->id }}"><i data-lucide="x" class="w-3.5 h-3.5"></i></button></form>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-400">Tidak ada lampiran.</p>
                @endforelse
            </div>
            @if ($canManage)
                <form method="POST" action="{{ route('surat-keluar.lampiran.store', $surat) }}" enctype="multipart/form-data" class="mt-4 flex gap-2">@csrf
                    <input type="file" name="lampiran[]" multiple required class="input !py-2 text-xs file:mr-2 file:rounded-full file:border-0 file:bg-ruby-50 file:px-3 file:py-1 file:text-ruby-800" data-testid="lampiran-upload-input">
                    <button class="btn btn-soft !px-3" data-testid="lampiran-upload-button"><i data-lucide="upload" class="w-4 h-4"></i></button>
                </form>
            @endif
        </section>

        <section class="card p-6" data-reveal data-testid="info-verifikasi-panel">
            <p class="eyebrow">Info Verifikasi</p>
            @forelse ($verifikasiInfo as $rev => $rows)
                <details class="mt-3 rounded-2xl border border-slate-100 p-3" @if ($loop->first) open @endif data-testid="info-verifikasi-rev-{{ $rev }}">
                    <summary class="cursor-pointer text-sm font-bold text-maroon-950">Versi revisi {{ $rev }} <span class="font-normal text-slate-400">· {{ $rows->count() }} tindakan</span></summary>
                    <ul class="mt-2 space-y-1.5">
                        @foreach ($rows->sortBy('id') as $r)
                            @php [$l, $i, $c] = $aksiLabel[$r->aksi] ?? [$r->aksi, 'dot', 'slate']; @endphp
                            <li class="flex items-start gap-2 text-xs"><x-badge :color="$c">{{ $l }}</x-badge><span class="text-slate-600"><b>{{ $r->user?->name ?? 'Sistem' }}</b> · {{ $r->created_at->translatedFormat('d M H:i') }}@if ($r->catatan)<br><span class="text-slate-500">“{{ $r->catatan }}”</span>@endif</span></li>
                        @endforeach
                    </ul>
                </details>
            @empty
                <p class="text-sm text-slate-400 mt-2">Belum ada tindakan verifikasi.</p>
            @endforelse
        </section>

        <section class="card p-6" data-reveal data-testid="info-revisi-panel">
            <p class="eyebrow !text-rose-600">Info Revisi</p>
            @forelse ($revisiInfo as $rev => $rows)
                <div class="mt-3" data-testid="info-revisi-rev-{{ $rev }}">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Revisi pada versi {{ $rev }}</p>
                    @foreach ($rows as $r)
                        <div class="mt-1.5 rounded-2xl bg-rose-50 px-3 py-2 text-sm text-rose-900"><b>{{ $r->user?->name }}</b>: {{ $r->catatan }}<span class="block text-[10px] text-rose-700/60">{{ $r->created_at->translatedFormat('d M Y H:i') }}</span></div>
                    @endforeach
                </div>
            @empty
                <p class="text-sm text-slate-400 mt-2">Belum ada catatan revisi.</p>
            @endforelse
        </section>

        <section class="card p-6" data-reveal data-testid="riwayat-panel">
            <p class="eyebrow">Riwayat</p>
            <ol class="relative border-l-2 border-ruby-100 ml-3 mt-4 space-y-4">
                @foreach ($surat->riwayats as $r)
                    @php [$l, $i, $c] = $aksiLabel[$r->aksi] ?? [$r->aksi, 'dot', 'slate']; @endphp
                    <li class="pl-6 relative"><span class="absolute -left-[11px] top-0 grid place-items-center w-5 h-5 rounded-full bg-{{ $c }}-500 text-white"><i data-lucide="{{ $i }}" class="w-3 h-3"></i></span>
                        <p class="text-sm font-bold text-slate-800">{{ $l }} <span class="font-normal text-slate-500">oleh {{ $r->user?->name ?? 'Sistem' }}</span></p>
                        @if ($r->catatan)<p class="text-xs text-slate-600 mt-0.5">“{{ $r->catatan }}”</p>@endif
                        <p class="text-[11px] text-slate-400">{{ $r->created_at->translatedFormat('d M Y H:i') }} · rev {{ $r->revisi }}</p></li>
                @endforeach
            </ol>
        </section>
        @if ($surat->status === 2)
            <a href="{{ route('verifikasi.show', $surat->uuid) }}" target="_blank" class="card card-hover p-5 flex items-center gap-3" data-testid="verifikasi-link"><i data-lucide="qr-code" class="w-8 h-8 text-ruby-600"></i><span><span class="block font-bold text-maroon-950">Halaman verifikasi publik</span><span class="block text-xs text-slate-500">Tujuan QR Code pada dokumen</span></span></a>
        @endif
    </aside>
</div>
@endsection

@if ($canManage)
@push('modals')
<div id="reupload-modal" class="modal-backdrop" data-testid="reupload-modal">
    <div class="modal-panel">
        <div class="flex items-center justify-between mb-4"><h3 class="font-display text-xl font-bold text-maroon-950">Unggah ulang file surat</h3><button type="button" class="btn-icon hover:bg-slate-100" data-modal-close><i data-lucide="x" class="w-5 h-5"></i></button></div>
        <form method="POST" action="{{ route('surat-keluar.reupload', $surat) }}" enctype="multipart/form-data" class="space-y-4">@csrf
            <input type="file" name="file" accept="application/pdf" required class="input" data-testid="reupload-file-input">
            <p class="text-xs text-slate-500">File saat ini: {{ $surat->file_name }}</p>
            <button class="btn btn-primary w-full" data-testid="reupload-submit-button"><i data-lucide="upload" class="w-4 h-4"></i> Ganti File</button>
        </form>
    </div>
</div>
@endpush
@endif
