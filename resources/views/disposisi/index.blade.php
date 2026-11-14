@extends('layouts.app')
@section('title', $mode === 'masuk' ? 'Disposisi Masuk' : 'Disposisi Keluar')
@section('overline', 'Surat Masuk')
@section('content')
@php
    $statuses = $mode === 'masuk' ? \App\Models\Disposisi::STATUS : ['draft' => 'Draft Sekpri'] + \App\Models\Disposisi::STATUS;
    $color = fn ($d) => $d->is_draft ? 'gold' : (['terkirim' => 'amber', 'dibaca' => 'sky', 'ditindaklanjuti' => 'ruby'][$d->status] ?? 'slate');
@endphp
<form class="flex flex-col sm:flex-row gap-2 mb-6" method="GET" data-reveal>
    <div class="relative flex-1 max-w-lg"><i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i><input name="q" value="{{ request('q') }}" class="input pl-11 rounded-full" placeholder="Cari perihal, nomor, pengirim…" data-testid="disposisi-search-input"></div>
    @if ($mode === 'masuk')<div class="relative sm:w-44"><i data-lucide="hash" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gold-600"></i><input name="tag" value="{{ request('tag') }}" class="input pl-10 rounded-full" placeholder="Tag" data-testid="disposisi-tag-filter"></div>@endif
    <select name="status" class="input rounded-full sm:w-52" onchange="this.form.submit()" data-testid="disposisi-status-filter"><option value="">Semua status</option>@foreach ($statuses as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select>
</form>
@if ($items->isEmpty())
    <x-empty title="{{ $mode === 'masuk' ? 'Belum ada disposisi untuk Anda' : 'Belum ada disposisi yang dikirim' }}" icon="git-pull-request-arrow">{{ $mode === 'masuk' ? 'Disposisi yang Anda terima akan muncul di sini.' : 'Disposisi yang Anda kirim (atau draft untuk pimpinan) akan muncul di sini.' }}</x-empty>
@else
    <div class="card overflow-hidden" data-reveal>
        <div class="overflow-x-auto">
            <table class="table-modern" data-testid="disposisi-table">
                <thead><tr><th>Surat</th><th>{{ $mode === 'masuk' ? 'Dari' : 'Kepada' }}</th><th>Instruksi</th><th>Batas</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($items as $d)
                        <tr data-testid="disposisi-row-{{ $d->id }}" class="{{ $mode === 'masuk' && $d->status === 'terkirim' ? 'bg-ruby-50/40 font-semibold' : '' }}">
                            <td class="max-w-xs"><a href="{{ route('surat-masuk.show', $d->surat_masuk_id) }}" class="font-bold text-maroon-950 hover:text-ruby-700 line-clamp-1">{{ $d->surat->perihal }}</a><p class="text-xs text-slate-400 mt-0.5">{{ $d->surat->pengirim }} · {{ $d->surat->nomor_surat }}</p></td>
                            <td class="text-sm whitespace-nowrap">
                                @if ($mode === 'masuk'){{ $d->dari->name }}<span class="block text-[11px] text-slate-400">{{ $d->dari->jabatan?->nama }}</span>
                                @else{{ $d->ke->name }}<span class="block text-[11px] text-slate-400">{{ $d->dari_user_id !== auth()->id() ? 'a.n. ' . $d->dari->name : ($d->ke->jabatan?->nama) }}{{ $d->drafter ? ' · disiapkan ' . $d->drafter->name : '' }}</span>@endif
                            </td>
                            <td class="text-sm text-slate-600 max-w-xs"><span class="line-clamp-2">{{ $d->instruksi }}</span></td>
                            <td class="text-xs whitespace-nowrap {{ $d->batas_waktu && $d->batas_waktu->isPast() && $d->status !== 'ditindaklanjuti' ? 'text-rose-600 font-bold' : 'text-slate-500' }}">{{ $d->batas_waktu?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td><x-badge :color="$color($d)">{{ $d->status_label }}</x-badge></td>
                            <td class="text-right whitespace-nowrap"><span class="text-[11px] text-slate-400 mr-2">{{ $d->created_at->diffForHumans() }}</span><a href="{{ route('surat-masuk.show', $d->surat_masuk_id) }}" class="btn btn-ghost !py-1.5" data-testid="disposisi-open-{{ $d->id }}">Buka</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $items->links() }}</div>
@endif
@endsection
