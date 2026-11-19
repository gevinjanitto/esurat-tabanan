@extends('layouts.app')
@section('title', 'Dashboard')
@section('overline', now()->translatedFormat('l, d F Y'))
@section('content')
@php $u = auth()->user(); $max = max(1, $months->max(fn ($m) => max($m['masuk'], $m['keluar']))); @endphp
<div class="grid grid-cols-1 lg:grid-cols-12 gap-5 lg:gap-6">
    {{-- Hero --}}
    <section class="lg:col-span-8 relative overflow-hidden rounded-[2rem] bg-crimson text-white p-6 sm:p-9 min-h-[220px]" data-reveal data-testid="dashboard-hero">
        <div class="absolute -right-10 -top-16 w-72 h-72 rounded-full bg-ruby-500/30 blur-3xl"></div>
        <div class="absolute right-24 -bottom-20 w-60 h-60 rounded-full bg-gold-500/20 blur-3xl"></div>
        <img src="{{ asset('images/gate.webp') }}" alt="" class="hidden md:block absolute right-40 bottom-0 h-[90%] max-h-60 object-contain opacity-20">
        <img src="{{ asset('images/woman.webp') }}" alt="" class="hidden sm:block absolute right-2 bottom-0 h-[105%] max-h-72 object-contain animate-floaty drop-shadow-2xl" data-testid="dashboard-hero-image">
        <div class="relative max-w-md">
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-ruby-300/80">Om Swastyastu,</p>
            <h2 class="font-display mt-2 text-3xl sm:text-4xl font-extrabold leading-tight">{{ \Illuminate\Support\Str::words($u->name, 3, '') }}</h2>
            <p class="mt-3 text-sm text-ruby-50/70">{{ $u->jabatan?->nama ?? $u->role_label }} · {{ $u->satker?->nama ?? 'Kab. Tabanan' }}</p>
            <div class="mt-6 flex flex-wrap gap-2.5">
                @unless ($u->hasRole('admin'))
                    <a href="{{ route('surat-keluar.create') }}" class="btn btn-gold" data-magnetic data-testid="dashboard-create-surat-keluar"><i data-lucide="plus" class="w-4 h-4"></i> Buat Surat</a>
                @endunless
                @if ($u->hasRole('operator'))
                    <a href="{{ route('surat-masuk.create') }}" class="btn bg-white/10 text-white hover:bg-white/20" data-testid="dashboard-create-surat-masuk"><i data-lucide="inbox" class="w-4 h-4"></i> Catat Surat Masuk</a>
                @endif
                <a href="{{ route('dokumen.index') }}" class="btn bg-white/10 text-white hover:bg-white/20" data-testid="dashboard-dokumen-link"><i data-lucide="folder-heart" class="w-4 h-4"></i> Dokumen Saya</a>
            </div>
        </div>
    </section>

    {{-- Status donut-ish --}}
    <section class="lg:col-span-4 card p-6" data-reveal>
        <p class="eyebrow">Surat Keluar</p>
        <h3 class="font-display text-lg font-bold text-maroon-950 mt-1">Status alur</h3>
        <div class="mt-5 space-y-3">
            @foreach (\App\Models\SuratKeluar::STATUS as $code => [$label, $color])
                @php $val = $statusBreakdown[$code] ?? 0; $total = max(1, $statusBreakdown->sum()); @endphp
                <div>
                    <div class="flex justify-between text-xs font-semibold mb-1.5"><span class="text-slate-600">{{ $label }}</span><span class="font-mono text-slate-500">{{ $val }}</span></div>
                    <div class="h-2 rounded-full bg-slate-100 overflow-hidden"><div class="h-full rounded-full bg-{{ $color }}-500 transition-all duration-1000" style="width: {{ $val / $total * 100 }}%"></div></div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Stats --}}
    @foreach ($stats as $i => $s)
        <a href="{{ $s['url'] }}" class="lg:col-span-3 card card-hover spotlight p-5 sm:p-6 group" data-reveal data-testid="dashboard-stat-{{ \Illuminate\Support\Str::slug($s['label']) }}">
            <div class="flex items-start justify-between">
                <span class="grid place-items-center w-11 h-11 rounded-2xl {{ $i === 2 ? 'bg-gold-500 text-maroon-950' : 'bg-blush-100 text-maroon-700' }} transition-transform group-hover:rotate-[-8deg] group-hover:scale-110"><i data-lucide="{{ $s['icon'] }}" class="w-5 h-5"></i></span>
                <i data-lucide="arrow-up-right" class="w-4 h-4 text-slate-300 group-hover:text-ruby-600 transition-colors"></i>
            </div>
            <p class="mt-5 font-display text-4xl font-extrabold text-maroon-950" data-count="{{ $s['value'] }}">0</p>
            <p class="text-sm font-semibold text-slate-500 mt-1">{{ $s['label'] }}</p>
        </a>
    @endforeach

    {{-- Tugas --}}
    <section class="lg:col-span-7 card p-6" data-reveal data-testid="dashboard-tasks">
        <div class="flex items-center justify-between mb-4">
            <div><p class="eyebrow">Menunggu Anda</p><h3 class="font-display text-lg font-bold text-maroon-950 mt-1">Verifikasi & tanda tangan</h3></div>
            <a href="{{ $stats[2]['url'] }}" class="text-sm font-semibold text-ruby-700 hover:underline">Lihat semua</a>
        </div>
        <div class="space-y-2.5">
            @forelse ($tugas as $t)
                <a href="{{ route('surat-keluar.review', $t) }}" class="flex items-center gap-4 rounded-2xl border border-slate-100 p-3.5 hover:border-ruby-300 hover:bg-ruby-50/40 transition-colors" data-testid="dashboard-task-{{ $t->id }}">
                    <span class="grid place-items-center w-11 h-11 shrink-0 rounded-2xl {{ $t->status === 10 ? 'bg-amber-100 text-amber-700' : 'bg-sky-100 text-sky-700' }}"><i data-lucide="{{ $t->status === 10 ? 'signature' : 'scan-search' }}" class="w-5 h-5"></i></span>
                    <span class="min-w-0 flex-1"><span class="block text-sm font-bold text-slate-800 truncate">{{ $t->perihal }}</span><span class="block text-xs text-slate-500 truncate">dari {{ $t->creator->name }} · {{ $t->updated_at->diffForHumans() }}</span></span>
                    <x-badge :color="$t->status_color" class="hidden sm:inline-flex">{{ $t->status === 10 ? 'Tanda tangan' : 'Verifikasi' }}</x-badge>
                </a>
            @empty
                <p class="text-sm text-slate-400 py-8 text-center">Tidak ada surat yang menunggu tindakan Anda.</p>
            @endforelse
        </div>
    </section>

    {{-- Chart --}}
    <section class="lg:col-span-5 card p-6" data-reveal>
        <p class="eyebrow">6 bulan terakhir</p>
        <h3 class="font-display text-lg font-bold text-maroon-950 mt-1">Volume surat</h3>
        <div class="mt-6 flex items-end gap-3 h-44" data-testid="dashboard-chart">
            @foreach ($months as $m)
                <div class="flex-1 flex flex-col items-center gap-2">
                    <div class="w-full flex items-end justify-center gap-1 h-36">
                        <div data-bar class="w-1/2 max-w-4 rounded-t-lg bg-gradient-to-t from-ruby-600 to-ruby-400" style="height: {{ max(4, $m['masuk'] / $max * 100) }}%" title="Masuk: {{ $m['masuk'] }}"></div>
                        <div data-bar class="w-1/2 max-w-4 rounded-t-lg bg-gradient-to-t from-gold-600 to-gold-400" style="height: {{ max(4, $m['keluar'] / $max * 100) }}%" title="Keluar: {{ $m['keluar'] }}"></div>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-500">{{ $m['label'] }}</span>
                </div>
            @endforeach
        </div>
        <div class="mt-4 flex gap-4 text-xs font-semibold text-slate-500"><span class="flex items-center gap-1.5"><i class="w-2.5 h-2.5 rounded-full bg-ruby-500"></i>Masuk</span><span class="flex items-center gap-1.5"><i class="w-2.5 h-2.5 rounded-full bg-gold-500"></i>Keluar</span></div>
    </section>

    {{-- Disposisi --}}
    <section class="lg:col-span-7 card p-6" data-reveal data-testid="dashboard-disposisi">
        <p class="eyebrow">Disposisi</p>
        <h3 class="font-display text-lg font-bold text-maroon-950 mt-1 mb-4">Belum ditindaklanjuti</h3>
        <div class="space-y-2.5">
            @forelse ($disposisi as $d)
                <a href="{{ route('surat-masuk.show', $d->surat_masuk_id) }}" class="flex items-center gap-4 rounded-2xl border border-slate-100 p-3.5 hover:border-ruby-300 transition-colors">
                    <span class="grid place-items-center w-11 h-11 shrink-0 rounded-2xl bg-blush-100 text-maroon-700"><i data-lucide="forward" class="w-5 h-5"></i></span>
                    <span class="min-w-0 flex-1"><span class="block text-sm font-bold text-slate-800 truncate">{{ $d->surat?->perihal }}</span><span class="block text-xs text-slate-500 truncate">{{ $d->instruksi }} — {{ $d->dari->name }}</span></span>
                    <x-badge :color="$d->status === 'terkirim' ? 'amber' : 'sky'">{{ $d->status_label }}</x-badge>
                </a>
            @empty
                <p class="text-sm text-slate-400 py-8 text-center">Semua disposisi sudah ditindaklanjuti.</p>
            @endforelse
        </div>
    </section>

    @if ($agendas !== null)
        <section class="lg:col-span-12 card p-6" data-reveal data-testid="dashboard-agenda">
            <div class="flex items-center justify-between mb-4"><div><p class="eyebrow">Agenda</p><h3 class="font-display text-lg font-bold text-maroon-950 mt-1">Akan datang</h3></div><a href="{{ route('agenda.index') }}" class="text-sm font-semibold text-ruby-700 hover:underline" data-testid="dashboard-agenda-all">Lihat semua</a></div>
            <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-3">
                @forelse ($agendas as $a)
                    <a href="{{ route('agenda.show', $a) }}" class="flex items-center gap-3 rounded-2xl border border-slate-100 p-3.5 hover:border-ruby-300 hover:bg-ruby-50/40 transition-colors" data-testid="dashboard-agenda-{{ $a->id }}">
                        <span class="grid place-items-center w-12 h-12 shrink-0 rounded-2xl {{ $a->mulai->isToday() ? 'bg-gold-500 text-maroon-950' : 'bg-maroon-950 text-white' }} text-center leading-none"><span><span class="block font-display text-lg font-extrabold">{{ $a->mulai->format('d') }}</span><span class="block text-[9px] font-bold uppercase">{{ $a->mulai->translatedFormat('M') }}</span></span></span>
                        <span class="min-w-0"><span class="block text-sm font-bold text-slate-800 truncate">{{ $a->judul }}</span><span class="block text-xs text-slate-500 truncate">{{ $a->mulai->format('H:i') }} · {{ $a->metode === 'online' ? 'Daring' : $a->tempat }}</span></span>
                    </a>
                @empty
                    <p class="text-sm text-slate-400 py-6 sm:col-span-2 xl:col-span-4 text-center">Tidak ada agenda dalam waktu dekat.</p>
                @endforelse
            </div>
        </section>
    @endif

    <section class="lg:col-span-5 card p-6" data-reveal>
        <p class="eyebrow">Riwayat</p>
        <h3 class="font-display text-lg font-bold text-maroon-950 mt-1 mb-4">Aktivitas terakhir Anda</h3>
        <ol class="relative border-l-2 border-dashed border-ruby-100 ml-2 space-y-4">
            @forelse ($activity as $a)
                <li class="pl-5 relative"><span class="absolute -left-[7px] top-1.5 w-3 h-3 rounded-full bg-ruby-500 ring-4 ring-white"></span>
                    <p class="text-sm font-semibold text-slate-700">{{ $a->deskripsi }}</p><p class="text-xs text-slate-400">{{ $a->created_at->diffForHumans() }}</p></li>
            @empty
                <li class="pl-5 text-sm text-slate-400">Belum ada aktivitas.</li>
            @endforelse
        </ol>
    </section>
</div>
@endsection
