@extends('layouts.app')
@section('title', 'Dokumen Saya')
@section('overline', 'Semua berkas yang terkait dengan Anda')
@section('content')
<div data-doc-hub>
    <section class="relative overflow-hidden rounded-[2rem] bg-crimson text-white p-6 sm:p-8 mb-6" data-reveal>
        <div class="absolute -right-16 -top-16 w-64 h-64 rounded-full bg-ruby-400/25 blur-3xl"></div>
        <div class="relative flex flex-col md:flex-row md:items-end gap-6 justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-ruby-300/80">Brankas dokumen</p>
                <p class="font-display mt-2 text-4xl sm:text-5xl font-extrabold"><span data-count="{{ $total }}">0</span> <span class="text-xl sm:text-2xl font-bold text-ruby-100/70">dokumen</span></p>
                <p class="mt-2 text-sm text-ruby-50/70 max-w-md">Surat yang Anda buat, verifikasi, tandatangani, catat, maupun disposisi — terkumpul di satu tempat dan siap dibagikan.</p>
            </div>
            <a href="{{ route('dokumen.links') }}" class="btn bg-white/10 hover:bg-white/20 text-white self-start md:self-auto" data-testid="dokumen-links-button"><i data-lucide="link-2" class="w-4 h-4"></i> Kelola Tautan</a>
        </div>
    </section>

    <div class="flex gap-2 overflow-x-auto scrollbar-thin pb-2 mb-4 -mx-1 px-1" data-reveal>
        <a href="{{ route('dokumen.index', request()->except('relasi')) }}" class="tab {{ !request('relasi') ? 'active' : 'bg-white border border-slate-200' }}" data-testid="dokumen-filter-semua">Semua</a>
        @foreach (\App\Services\DocumentHub::RELASI as $k => $label)
            <a href="{{ route('dokumen.index', ['relasi' => $k] + request()->except('relasi')) }}" class="tab flex items-center gap-1.5 {{ request('relasi') === $k ? 'active' : 'bg-white border border-slate-200' }}" data-testid="dokumen-filter-{{ $k }}">{{ $label }} <span class="font-mono text-[10px] opacity-70">{{ $counts[$k] }}</span></a>
        @endforeach
    </div>

    <form class="flex flex-col sm:flex-row gap-2 mb-6" method="GET" data-reveal>
        @if (request('relasi'))<input type="hidden" name="relasi" value="{{ request('relasi') }}">@endif
        <div class="relative flex-1"><i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i><input name="q" value="{{ request('q') }}" class="input pl-11 rounded-full" placeholder="Cari perihal atau nomor…" data-testid="dokumen-search-input"></div>
        <select name="jenis" class="input rounded-full sm:w-48" onchange="this.form.submit()" data-testid="dokumen-jenis-filter"><option value="">Semua jenis</option><option value="keluar" @selected(request('jenis') === 'keluar')>Surat Keluar</option><option value="masuk" @selected(request('jenis') === 'masuk')>Surat Masuk</option></select>
        <button type="button" id="select-all" class="btn btn-ghost" data-testid="dokumen-select-all"><i data-lucide="list-checks" class="w-4 h-4"></i> Pilih semua</button>
    </form>

    @if ($items->isEmpty())
        <x-empty title="Belum ada dokumen" icon="folder-heart">Dokumen akan muncul otomatis saat Anda terlibat dalam surat masuk atau keluar.</x-empty>
    @else
        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach ($items as $it)
                <div class="card card-hover spotlight p-5 flex flex-col" data-reveal data-testid="dokumen-card-{{ $it['key'] }}">
                    <div class="flex items-start gap-3">
                        <label class="relative mt-0.5 cursor-pointer {{ $it['has_file'] ? '' : 'opacity-30 pointer-events-none' }}">
                            <input type="checkbox" value="{{ $it['key'] }}" class="peer sr-only" data-doc-check @disabled(!$it['has_file']) data-testid="dokumen-check-{{ $it['key'] }}">
                            <span class="grid place-items-center w-6 h-6 rounded-lg border-2 border-slate-300 transition-colors peer-checked:bg-ruby-600 peer-checked:border-ruby-600 text-white"><i data-lucide="check" class="w-3.5 h-3.5"></i></span>
                        </label>
                        <span class="grid place-items-center w-11 h-11 shrink-0 rounded-2xl {{ $it['type'] === 'keluar' ? 'bg-[#fbf3d5] text-gold-600' : 'bg-blush-100 text-maroon-700' }}"><i data-lucide="{{ $it['type'] === 'keluar' ? 'send' : 'inbox' }}" class="w-5 h-5"></i></span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ $it['url'] }}" class="font-bold text-maroon-950 leading-snug line-clamp-2 hover:text-ruby-700">{{ $it['perihal'] }}</a>
                            <p class="text-xs font-mono text-slate-400 mt-1 truncate">{{ $it['nomor'] }}</p>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-1.5">
                        <x-badge :color="$it['color']">{{ $it['status'] }}</x-badge>
                        @foreach ($it['relasi'] as $r)<span class="badge badge-gold">{{ \App\Services\DocumentHub::RELASI[$r] }}</span>@endforeach
                    </div>
                    <div class="mt-auto pt-4 flex items-center justify-between">
                        <span class="text-xs text-slate-500">{{ optional($it['tanggal'])->translatedFormat('d M Y') }}</span>
                        <div class="flex gap-1">
                            @if ($it['has_file'])
                                <a href="{{ $it['file_url'] }}?download=1" class="btn-icon text-slate-500 hover:bg-ruby-50 hover:text-maroon-700" title="Unduh" data-testid="dokumen-download-{{ $it['key'] }}"><i data-lucide="download" class="w-4 h-4"></i></a>
                                <button type="button" data-share-one="{{ $it['key'] }}" class="btn-icon text-slate-500 hover:bg-ruby-50 hover:text-maroon-700" title="Bagikan" data-testid="dokumen-share-one-{{ $it['key'] }}"><i data-lucide="share-2" class="w-4 h-4"></i></button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div id="share-bar" class="fixed z-50 left-1/2 -translate-x-1/2 bottom-28 lg:bottom-8 translate-y-[160%] transition-transform duration-500 ease-[cubic-bezier(.2,.9,.25,1.2)]" data-testid="share-selection-bar">
        <div class="flex items-center gap-3 rounded-full bg-maroon-950 text-white pl-5 pr-2 py-2 shadow-2xl shadow-ruby-950/40">
            <span class="text-sm font-semibold whitespace-nowrap"><span data-selected-count>0</span> dipilih</span>
            <button type="button" class="btn btn-gold !py-2" data-modal-open="share-modal" data-testid="dokumen-saya-share-link-button"><i data-lucide="link-2" class="w-4 h-4"></i> Buat Tautan</button>
        </div>
    </div>
</div>
@endsection

@push('modals')
<div id="share-modal" class="modal-backdrop" data-testid="share-modal">
    <div class="modal-panel">
        <div class="flex items-start justify-between mb-5">
            <div><p class="eyebrow">Bagikan dokumen</p><h3 class="font-display text-2xl font-bold text-maroon-950 mt-1">Buat tautan berbagi</h3></div>
            <button type="button" class="btn-icon hover:bg-slate-100" data-modal-close data-testid="share-modal-close"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form id="share-form" action="{{ route('dokumen.share') }}" class="space-y-5">
            <div><label class="label">Judul tautan (opsional)</label><input name="judul" class="input" placeholder="mis. Berkas pengadaan untuk auditor" data-testid="share-judul-input"></div>
            <div>
                <label class="label">Hak akses</label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach (['view' => ['eye', 'Hanya lihat', 'Tidak bisa unduh'], 'download' => ['download', 'Lihat & unduh', 'Penerima bisa menyimpan']] as $k => [$ic, $l, $d])
                        <label class="cursor-pointer"><input type="radio" name="permission" value="{{ $k }}" class="peer sr-only" @checked($k === 'view') data-testid="share-permission-{{ $k }}">
                            <span class="flex flex-col gap-1 rounded-2xl border-2 border-slate-200 p-3.5 transition-colors peer-checked:border-ruby-500 peer-checked:bg-ruby-50"><i data-lucide="{{ $ic }}" class="w-5 h-5 text-maroon-700"></i><span class="text-sm font-bold text-maroon-950">{{ $l }}</span><span class="text-[11px] text-slate-500">{{ $d }}</span></span></label>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="label">Masa aktif</label>
                <div class="flex flex-wrap gap-2">
                    @foreach (['1h' => '1 jam', '1d' => '1 hari', '7d' => '7 hari', '30d' => '30 hari', 'never' => 'Selamanya', 'custom' => 'Atur sendiri'] as $k => $l)
                        <label class="cursor-pointer"><input type="radio" name="durasi" value="{{ $k }}" class="peer sr-only" @checked($k === '7d') data-testid="share-durasi-{{ $k }}"><span class="block rounded-full border-2 border-slate-200 px-3.5 py-1.5 text-sm font-semibold text-slate-600 transition-colors peer-checked:border-ruby-500 peer-checked:bg-ruby-50 peer-checked:text-maroon-900">{{ $l }}</span></label>
                    @endforeach
                </div>
                <input type="datetime-local" name="expires_at" id="custom-expiry" class="input mt-3 hidden" data-testid="share-custom-expiry-input">
            </div>
            <button type="submit" class="btn btn-primary w-full py-3" data-testid="share-submit-button"><i data-lucide="sparkles" class="w-4 h-4"></i> Buat Tautan</button>
        </form>
        <div id="share-result" class="hidden mt-6 rounded-3xl bg-gradient-to-br from-blush-100 to-white border border-ruby-200 p-5" data-testid="share-result">
            <p class="flex items-center gap-2 font-bold text-maroon-900"><i data-lucide="party-popper" class="w-5 h-5"></i> Tautan siap dibagikan</p>
            <p id="share-meta" class="text-xs text-slate-500 mt-1"></p>
            <div class="mt-3 flex gap-2"><input id="share-url" readonly class="input font-mono text-xs" data-testid="share-url-input"><button id="share-copy" type="button" data-copy="" class="btn btn-primary !px-4" data-testid="share-copy-button"><i data-lucide="copy" class="w-4 h-4"></i></button></div>
            <a id="share-wa" target="_blank" rel="noopener" class="btn btn-soft w-full mt-2" data-testid="share-whatsapp-button"><i data-lucide="message-circle" class="w-4 h-4"></i> Kirim via WhatsApp</a>
        </div>
    </div>
</div>
@endpush
