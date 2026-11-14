@extends('layouts.app')
@section('title', 'Teruskan Surat Masuk')
@section('overline', $surat->nomor_agenda)
@section('content')
@php
    $tabs = ['direksi' => ['Direksi', 'crown'], 'divisi' => ['Divisi / Satker', 'building-2'], 'jabatan' => ['Jabatan', 'briefcase-business']];
    $aktif = $surat->teruskans;
    $sentUser = $aktif->pluck('user_id')->filter()->all();
    $sentSatker = $aktif->whereNull('user_id')->pluck('satker_id')->all();
@endphp
<div class="grid xl:grid-cols-[1fr_380px] gap-6">
    <div class="min-w-0 space-y-6">
        <section class="card p-6" data-reveal>
            <p class="eyebrow">Surat</p>
            <a href="{{ route('surat-masuk.show', $surat) }}" class="mt-1 block font-display text-xl font-bold text-maroon-950 hover:text-maroon-700" data-testid="teruskan-surat-perihal">{{ $surat->perihal }}</a>
            <p class="text-sm text-slate-500">{{ $surat->pengirim }} · {{ $surat->nomor_surat }}</p>
        </section>
        <div class="flex flex-col sm:flex-row sm:items-center gap-3" data-reveal>
            <div class="flex gap-1 overflow-x-auto scrollbar-thin rounded-full bg-white border border-slate-200 p-1">
                @foreach ($tabs as $k => [$l, $i])
                    <a href="{{ route('surat-masuk.teruskan.form', [$surat, 'tab' => $k]) }}" class="tab flex items-center gap-1.5 {{ $tab === $k ? 'active' : '' }}" data-testid="teruskan-tab-{{ $k }}"><i data-lucide="{{ $i }}" class="w-4 h-4"></i>{{ $l }}</a>
                @endforeach
            </div>
            <form class="relative flex-1 sm:max-w-xs sm:ml-auto" method="GET"><input type="hidden" name="tab" value="{{ $tab }}"><i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i><input name="q" value="{{ request('q') }}" class="input pl-11 rounded-full" placeholder="Cari…" data-testid="teruskan-search-input"></form>
        </div>
        <div class="card overflow-hidden" data-reveal>
            <table class="table-modern" data-testid="teruskan-table">
                <thead><tr><th>{{ $tab === 'divisi' ? 'Satuan kerja' : 'Nama' }}</th><th>{{ $tab === 'divisi' ? 'Penerima (hak disposisi awal)' : 'Jabatan / Satker' }}</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($rows as $r)
                        @php $done = $tab === 'divisi' ? in_array($r->id, $sentSatker) : in_array($r->id, $sentUser); @endphp
                        <tr data-testid="teruskan-row-{{ $r->id }}">
                            <td class="font-semibold text-maroon-950">{{ $tab === 'divisi' ? $r->nama : $r->name }}</td>
                            <td class="text-slate-600 text-sm">{{ $tab === 'divisi' ? $r->penerima_count . ' pengguna' : ($r->jabatan?->nama ?? '—') . ' · ' . ($r->satker?->nama ?? '—') }}</td>
                            <td class="text-right">
                                @if ($done)
                                    <span class="badge badge-ruby"><i data-lucide="check" class="w-3 h-3"></i> Diteruskan</span>
                                @else
                                    <form method="POST" action="{{ route('surat-masuk.teruskan', $surat) }}">@csrf<input type="hidden" name="tipe" value="{{ $tab }}"><input type="hidden" name="target_id" value="{{ $r->id }}"><button class="btn btn-primary !py-1.5" data-testid="teruskan-submit-{{ $r->id }}"><i data-lucide="forward" class="w-4 h-4"></i> Teruskan</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-slate-400 py-10">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <aside class="space-y-6">
        <section class="card p-6" data-reveal data-testid="teruskan-aktif-panel">
            <p class="eyebrow">Sudah diteruskan</p>
            <div class="mt-3 space-y-2">
                @forelse ($aktif as $t)
                    <div class="flex items-center gap-3 rounded-2xl border border-slate-100 p-3" data-testid="teruskan-aktif-{{ $t->id }}">
                        <span class="grid place-items-center w-9 h-9 rounded-xl bg-blush-100 text-maroon-700"><i data-lucide="{{ $t->user_id ? 'user-round' : 'building-2' }}" class="w-4 h-4"></i></span>
                        <div class="min-w-0 flex-1"><p class="text-sm font-bold text-slate-800 truncate">{{ $t->target_label }}</p><p class="text-[11px] text-slate-500">{{ \App\Models\SuratMasukTeruskan::TIPE[$t->tipe] ?? $t->tipe }} · {{ $t->dibaca_at ? 'Dibaca' : 'Belum dibaca' }}</p></div>
                        <form method="POST" action="{{ route('surat-masuk.teruskan.batal', $t) }}" data-confirm="Batalkan teruskan ke {{ $t->target_label }}?">@csrf @method('DELETE')<button class="btn-icon text-slate-400 hover:text-rose-600 hover:bg-rose-50" title="Batalkan" data-testid="teruskan-batal-{{ $t->id }}"><i data-lucide="undo-2" class="w-4 h-4"></i></button></form>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">Belum diteruskan ke siapa pun.</p>
                @endforelse
            </div>
            <a href="{{ route('surat-masuk.show', $surat) }}" class="btn btn-ghost w-full mt-4" data-testid="teruskan-back-button"><i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali ke detail</a>
        </section>
    </aside>
</div>
@endsection
