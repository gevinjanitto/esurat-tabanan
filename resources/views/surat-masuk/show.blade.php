@extends('layouts.app')
@section('title', 'Detail Surat Masuk')
@section('overline', $surat->nomor_agenda)
@push('head') @vite('resources/js/pdf-viewer.js') @endpush
@section('content')
@php
    $me = auth()->user();
    $dColor = fn ($d) => $d->is_draft ? 'gold' : (['terkirim' => 'amber', 'dibaca' => 'sky', 'ditindaklanjuti' => 'ruby'][$d->status] ?? 'slate');
    $aktif = $surat->teruskans->where('status', 'aktif');
    $editable = $surat->disposisis->filter(fn ($d) => !$d->is_draft && $d->canManage($me) && $d->isMutable());
@endphp
<div class="grid xl:grid-cols-[1fr_400px] gap-6">
    <div class="space-y-6 min-w-0">
        <section class="card p-6 sm:p-8" data-reveal>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex flex-wrap gap-2 mb-3">
                        <x-badge :color="['baru' => 'sky', 'disposisi' => 'amber', 'selesai' => 'ruby'][$surat->status] ?? 'slate'" data-testid="surat-masuk-status">{{ $surat->status_label }}</x-badge>
                        <span class="badge {{ $surat->jenis === 'internal' ? 'badge-ruby' : 'badge-slate' }}">{{ $surat->jenis === 'internal' ? 'Internal' : 'Eksternal' }}</span>
                        @if ($surat->sifat)<x-badge :color="$surat->sifat->warna">{{ $surat->sifat->nama }}</x-badge>@endif
                        @if ($surat->keamanan)<span class="badge badge-gold">{{ $surat->keamanan->nama }}</span>@endif
                    </div>
                    <h2 class="font-display text-2xl sm:text-3xl font-bold tracking-tight text-maroon-950" data-testid="surat-masuk-perihal">{{ $surat->perihal }}</h2>
                    <p class="text-slate-500 mt-1">{{ $surat->pengirim }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if ($me->hasRole('operator'))
                        <a href="{{ route('surat-masuk.teruskan.form', $surat) }}" class="btn btn-primary" data-testid="surat-masuk-teruskan-button"><i data-lucide="forward" class="w-4 h-4"></i> Teruskan</a>
                        <a href="{{ route('surat-masuk.edit', $surat) }}" class="btn btn-ghost" data-testid="surat-masuk-edit-button"><i data-lucide="pencil" class="w-4 h-4"></i> Ubah</a>
                        <form method="POST" action="{{ route('surat-masuk.destroy', $surat) }}" data-confirm="Hapus surat masuk ini?">@csrf @method('DELETE')<button class="btn btn-danger" data-testid="surat-masuk-delete-button"><i data-lucide="trash-2" class="w-4 h-4"></i></button></form>
                    @endif
                    @if ($canTeruskanSekpri)
                        <form method="POST" action="{{ route('surat-masuk.teruskan-sekpri', $surat) }}" class="flex gap-2">@csrf
                            @php $sekpris = $me->sekpris; @endphp
                            @if ($sekpris->count() > 1)<select name="sekpri_id" class="input !py-2 rounded-full">@foreach ($sekpris as $sp)<option value="{{ $sp->id }}">{{ $sp->name }}</option>@endforeach</select>@else<input type="hidden" name="sekpri_id" value="{{ $sekpris->first()->id }}">@endif
                            <button class="btn btn-soft" data-testid="surat-masuk-teruskan-sekpri-button"><i data-lucide="user-round-check" class="w-4 h-4"></i> Teruskan ke Sekpri</button>
                        </form>
                    @endif
                    <a href="{{ route('disposisi.print', $surat) }}" target="_blank" class="btn btn-ghost" data-no-transition data-testid="surat-masuk-print-disposisi-button"><i data-lucide="printer" class="w-4 h-4"></i> Lembar Disposisi</a>
                    @if ($me->hasRole('operator', 'sekpri', 'user'))
                        <a href="{{ route('agenda.create', ['surat' => $surat->id]) }}" class="btn btn-ghost" data-testid="surat-masuk-agenda-button"><i data-lucide="calendar-plus" class="w-4 h-4"></i> Agenda</a>
                    @endif
                    <a href="{{ route('disposisi.riwayat', $surat) }}" class="btn btn-ghost" data-testid="surat-masuk-riwayat-button"><i data-lucide="git-fork" class="w-4 h-4"></i> Riwayat</a>
                    @if ($surat->status !== 'selesai' && $me->hasRole('operator', 'pimpinan', 'sekpri'))
                        <form method="POST" action="{{ route('surat-masuk.selesai', $surat) }}">@csrf<button class="btn btn-soft" data-testid="surat-masuk-selesai-button"><i data-lucide="check-check" class="w-4 h-4"></i> Selesai</button></form>
                    @endif
                </div>
            </div>
            <dl class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                @foreach (['Nomor Surat' => $surat->nomor_surat, 'Tanggal Surat' => $surat->tanggal_surat->translatedFormat('d M Y'), 'Diterima' => $surat->tanggal_terima->translatedFormat('d M Y'), 'Klasifikasi' => $surat->klasifikasi?->nama ?? '—'] as $k => $v)
                    <div class="rounded-2xl bg-slate-50 p-3.5"><dt class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $k }}</dt><dd class="mt-1 font-semibold text-slate-800 break-words">{{ $v }}</dd></div>
                @endforeach
            </dl>
            @if ($surat->ringkasan)<p class="mt-5 text-sm leading-relaxed text-slate-600">{{ $surat->ringkasan }}</p>@endif
            @if ($surat->agendas->isNotEmpty())
                <div class="mt-5 flex flex-wrap gap-2" data-testid="surat-masuk-agenda-list">
                    @foreach ($surat->agendas as $ag)<a href="{{ route('agenda.show', $ag) }}" class="badge badge-gold"><i data-lucide="calendar-days" class="w-3 h-3"></i>{{ $ag->judul }} · {{ $ag->mulai->translatedFormat('d M H:i') }}</a>@endforeach
                </div>
            @endif
            @if ($aktif->isNotEmpty())
                <div class="mt-5 flex flex-wrap items-center gap-1.5 text-xs text-slate-500" data-testid="surat-masuk-teruskan-list"><i data-lucide="forward" class="w-3.5 h-3.5"></i> Diteruskan ke:
                    @foreach ($aktif as $t)<span class="badge badge-slate" title="{{ \App\Models\SuratMasukTeruskan::TIPE[$t->tipe] ?? '' }}">{{ $t->target_label }}{{ $t->tipe === 'sekpri' ? ' (Sekpri)' : '' }}</span>@endforeach
                </div>
            @endif
        </section>

        @if ($surat->file_path)
            <section class="card p-3 sm:p-5" data-pdf-shell data-reveal>
                <div class="flex items-center justify-between px-2 pb-3">
                    <p class="font-bold text-maroon-950 flex items-center gap-2"><i data-lucide="file-text" class="w-4 h-4 text-ruby-600"></i> Dokumen</p>
                    <div class="flex gap-1.5">
                        <button class="btn-icon btn-ghost" data-zoom-out data-testid="pdf-zoom-out" aria-label="Perkecil"><i data-lucide="zoom-out" class="w-4 h-4"></i></button>
                        <button class="btn-icon btn-ghost" data-zoom-in data-testid="pdf-zoom-in" aria-label="Perbesar"><i data-lucide="zoom-in" class="w-4 h-4"></i></button>
                        <a href="{{ route('surat-masuk.file', [$surat, 'download' => 1]) }}" class="btn btn-soft" data-testid="surat-masuk-download-button"><i data-lucide="download" class="w-4 h-4"></i><span class="hidden sm:inline">Unduh</span></a>
                    </div>
                </div>
                <div class="rounded-2xl bg-slate-100/80 p-2 sm:p-6 max-h-[80vh] overflow-auto scrollbar-thin">
                    <div data-pdf-viewer data-src="{{ route('surat-masuk.file', $surat) }}"></div>
                </div>
            </section>
        @endif
    </div>

    <aside class="space-y-6">
        @if ($myDisposisi && $myDisposisi->status !== 'ditindaklanjuti')
            <section class="card p-6 border-gold-400/40 bg-gradient-to-br from-[#fffbeb] to-white" data-reveal data-testid="my-disposisi-card">
                <p class="eyebrow !text-gold-600">Disposisi untuk Anda</p>
                <p class="mt-2 font-bold text-maroon-950">{{ $myDisposisi->instruksi }}</p>
                @if ($myDisposisi->catatan)<p class="text-sm text-slate-600 mt-1">{{ $myDisposisi->catatan }}</p>@endif
                <p class="text-xs text-slate-500 mt-2">dari {{ $myDisposisi->dari->name }} @if ($myDisposisi->batas_waktu)· batas {{ $myDisposisi->batas_waktu->translatedFormat('d M Y') }}@endif</p>
                <div class="mt-4">@include('partials.tags', ['type' => 'disposisi', 'id' => $myDisposisi->id, 'tags' => $myDisposisiTags])</div>
                <form method="POST" action="{{ route('disposisi.tindak-lanjut', $myDisposisi) }}" class="mt-4 space-y-3">@csrf
                    <textarea name="tindak_lanjut" rows="3" class="input" placeholder="Laporan tindak lanjut…" required data-testid="tindak-lanjut-input"></textarea>
                    <button class="btn btn-gold w-full" data-testid="tindak-lanjut-submit-button"><i data-lucide="check" class="w-4 h-4"></i> Simpan Tindak Lanjut</button>
                </form>
            </section>
        @endif

        @if ($drafts->isNotEmpty())
            <section class="card p-6 border-gold-400/40" data-reveal data-testid="draft-disposisi-panel">
                <p class="eyebrow !text-gold-600">Draft disposisi Sekpri</p>
                <div class="mt-3 space-y-3">
                    @foreach ($drafts as $d)
                        <div class="rounded-2xl border border-slate-100 p-3" data-testid="draft-disposisi-{{ $d->id }}">
                            <p class="text-sm font-bold text-slate-800">Kepada {{ $d->ke->name }}</p>
                            <p class="text-sm text-slate-600">{{ $d->instruksi }}@if ($d->catatan) — {{ $d->catatan }}@endif</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">Disiapkan {{ $d->drafter?->name }} · a.n. {{ $d->dari->name }}</p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @if ($d->dari_user_id === $me->id)
                                    <form method="POST" action="{{ route('disposisi.kirim-draft', $d) }}">@csrf<button class="btn btn-primary !py-1.5 text-xs" data-testid="draft-approve-{{ $d->id }}"><i data-lucide="check" class="w-3.5 h-3.5"></i> Setujui & Kirim</button></form>
                                @endif
                                <button type="button" class="btn btn-ghost !py-1.5 text-xs" data-modal-open="disposisi-edit-{{ $d->id }}" data-testid="draft-edit-{{ $d->id }}"><i data-lucide="pencil" class="w-3.5 h-3.5"></i> Ubah</button>
                                <form method="POST" action="{{ route('disposisi.destroy', $d) }}" data-confirm="Hapus draft disposisi ini?">@csrf @method('DELETE')<button class="btn btn-danger !py-1.5 text-xs" data-testid="draft-delete-{{ $d->id }}"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if (($canDispose || $sekpriMode) && $surat->status !== 'selesai')
            <section class="card p-6" data-reveal>
                <p class="eyebrow">{{ $sekpriMode ? 'Draft untuk ' . $me->atasan?->name : 'Disposisi' }}</p>
                <h3 class="font-display text-lg font-bold text-maroon-950 mt-1 mb-4">{{ $sekpriMode ? 'Siapkan disposisi pimpinan' : 'Kirim disposisi' }}</h3>
                <form method="POST" action="{{ route('surat-masuk.disposisi', $surat) }}" class="space-y-4" data-testid="disposisi-form">@csrf
                    @if ($sekpriMode)<input type="hidden" name="as_draft" value="1">@endif
                    @include('disposisi._fields', ['id' => 'new'])
                    <button class="btn btn-primary w-full" data-testid="disposisi-submit-button"><i data-lucide="{{ $sekpriMode ? 'file-pen-line' : 'send' }}" class="w-4 h-4"></i> {{ $sekpriMode ? 'Kirim Draft ke Pimpinan' : 'Kirim Disposisi' }}</button>
                </form>
            </section>
        @endif

        <section class="card p-6" data-reveal data-testid="surat-masuk-tags-panel"><p class="eyebrow mb-3">Tag saya</p>@include('partials.tags', ['type' => 'surat_masuk', 'id' => $surat->id, 'tags' => $tags])</section>

        <section class="card p-6" data-reveal data-testid="disposisi-timeline">
            <div class="flex items-center justify-between"><div><p class="eyebrow">Jejak</p><h3 class="font-display text-lg font-bold text-maroon-950 mt-1">Alur disposisi</h3></div><a href="{{ route('disposisi.riwayat', $surat) }}" class="text-xs font-semibold text-ruby-700 hover:underline" data-testid="timeline-tree-link">Lihat pohon</a></div>
            <ol class="relative border-l-2 border-ruby-100 ml-3 space-y-5 mt-5">
                <li class="pl-6 relative"><span class="absolute -left-[11px] top-0 grid place-items-center w-5 h-5 rounded-full bg-maroon-900 text-white"><i data-lucide="inbox" class="w-3 h-3"></i></span>
                    <p class="text-sm font-bold text-slate-800">{{ $surat->jenis === 'internal' ? 'Dikirim otomatis dari surat keluar' : 'Dicatat oleh ' . $surat->creator->name }}</p><p class="text-xs text-slate-400">{{ $surat->created_at->translatedFormat('d M Y H:i') }}</p></li>
                @foreach ($disposisiList as $d)
                    <li class="pl-6 relative" data-testid="timeline-disposisi-{{ $d->id }}"><span class="absolute -left-[11px] top-0 grid place-items-center w-5 h-5 rounded-full {{ $d->status === 'ditindaklanjuti' ? 'bg-ruby-500' : 'bg-gold-500' }} text-white"><i data-lucide="corner-down-right" class="w-3 h-3"></i></span>
                        <p class="text-sm font-bold text-slate-800">{{ $d->dari->name }} <span class="font-normal text-slate-400">→</span> {{ $d->ke->name }}</p>
                        <p class="text-sm text-slate-600">{{ $d->instruksi }}@if ($d->catatan) — {{ $d->catatan }}@endif</p>
                        <div class="flex flex-wrap items-center gap-2 mt-1"><x-badge :color="$dColor($d)">{{ $d->status_label }}</x-badge><span class="text-xs text-slate-400">{{ $d->created_at->translatedFormat('d M H:i') }}</span>
                            @if ($editable->contains('id', $d->id))
                                <button type="button" class="text-xs font-semibold text-ruby-700 hover:underline" data-modal-open="disposisi-edit-{{ $d->id }}" data-testid="disposisi-edit-{{ $d->id }}">Ubah</button>
                                <form method="POST" action="{{ route('disposisi.destroy', $d) }}" class="inline" data-confirm="Hapus disposisi ini?">@csrf @method('DELETE')<button class="text-xs font-semibold text-rose-600 hover:underline" data-testid="disposisi-delete-{{ $d->id }}">Hapus</button></form>
                            @endif
                        </div>
                        @if ($d->tindak_lanjut)<p class="mt-2 text-xs rounded-xl bg-ruby-50 text-ruby-900 px-3 py-2">{{ $d->tindak_lanjut }}</p>@endif
                        @php $canComment = in_array($me->id, [$d->dari_user_id, $d->ke_user_id], true); @endphp
                        @if ($d->comments->isNotEmpty() || $canComment)
                            <details id="disposisi-{{ $d->id }}" class="mt-2 group/c" @if ($d->comments->isNotEmpty()) open @endif data-testid="disposisi-comments-{{ $d->id }}">
                                <summary class="cursor-pointer list-none text-xs font-semibold text-ruby-700 flex items-center gap-1"><i data-lucide="messages-square" class="w-3.5 h-3.5"></i> Tanggapan ({{ $d->comments->count() }})</summary>
                                <div class="mt-2 space-y-1.5">
                                    @foreach ($d->comments as $c)
                                        <div class="rounded-xl {{ $c->user_id === $me->id ? 'bg-maroon-950 text-white ml-6' : 'bg-slate-100 text-slate-700 mr-6' }} px-3 py-2 text-xs" data-testid="disposisi-comment-{{ $c->id }}">
                                            <p class="font-bold">{{ $c->user->name }} <span class="font-normal opacity-60">· {{ $c->created_at->translatedFormat('d M H:i') }}</span>
                                                @if ($c->user_id === $me->id)<form method="POST" action="{{ route('disposisi.comment.destroy', $c) }}" class="inline" data-confirm="Hapus tanggapan?">@csrf @method('DELETE')<button class="opacity-60 hover:opacity-100 ml-1" data-testid="disposisi-comment-delete-{{ $c->id }}">hapus</button></form>@endif</p>
                                            <p class="mt-0.5 whitespace-pre-line">{{ $c->komentar }}</p>
                                        </div>
                                    @endforeach
                                    @if ($canComment)
                                        <form method="POST" action="{{ route('disposisi.comment', $d) }}" class="flex gap-1.5">@csrf
                                            <input name="komentar" class="input !py-1.5 text-xs" placeholder="Tulis tanggapan…" required data-testid="disposisi-comment-input-{{ $d->id }}">
                                            <button class="btn btn-soft !px-3 !py-1.5" data-testid="disposisi-comment-send-{{ $d->id }}"><i data-lucide="send" class="w-3.5 h-3.5"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </details>
                        @endif
                    </li>
                @endforeach
            </ol>
        </section>
    </aside>
</div>
@endsection

@push('modals')
@foreach ($editable->merge($drafts) as $d)
    <div id="disposisi-edit-{{ $d->id }}" class="modal-backdrop" data-testid="disposisi-edit-modal-{{ $d->id }}">
        <div class="modal-panel">
            <div class="flex items-center justify-between mb-4"><h3 class="font-display text-xl font-bold text-maroon-950">Ubah disposisi ke {{ $d->ke->name }}</h3><button type="button" class="btn-icon hover:bg-slate-100" data-modal-close><i data-lucide="x" class="w-5 h-5"></i></button></div>
            <form method="POST" action="{{ route('disposisi.update', $d) }}" class="space-y-4">@csrf @method('PUT')
                @include('disposisi._fields', ['disposisi' => $d, 'id' => $d->id])
                <button class="btn btn-primary w-full" data-testid="disposisi-edit-save-{{ $d->id }}"><i data-lucide="save" class="w-4 h-4"></i> Simpan Perubahan</button>
            </form>
        </div>
    </div>
@endforeach
@endpush
