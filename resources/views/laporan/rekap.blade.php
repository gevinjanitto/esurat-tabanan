@extends('layouts.app')
@section('title', 'Rekap Jumlah Surat')
@section('overline', 'Laporan')
@section('content')
@php $total = fn ($k) => $rows->sum($k); @endphp
<form method="GET" class="card p-5 mb-6 flex flex-col sm:flex-row sm:items-end gap-3" data-reveal data-testid="rekap-filter-form">
    <div><label class="label">Tanggal awal</label><input type="date" name="tgl_awal" value="{{ $awal->toDateString() }}" class="input" data-testid="rekap-tgl-awal"></div>
    <div><label class="label">Tanggal akhir</label><input type="date" name="tgl_akhir" value="{{ $akhir->toDateString() }}" class="input" data-testid="rekap-tgl-akhir"></div>
    <button class="btn btn-primary" data-testid="rekap-filter-button"><i data-lucide="filter" class="w-4 h-4"></i> Tampilkan</button>
    <a href="{{ route('laporan.rekap.export', ['tgl_awal' => $awal->toDateString(), 'tgl_akhir' => $akhir->toDateString()]) }}" class="btn btn-soft sm:ml-auto" data-testid="rekap-export-button"><i data-lucide="file-spreadsheet" class="w-4 h-4"></i> Ekspor Excel</a>
</form>
<div class="grid sm:grid-cols-3 gap-4 mb-6" data-reveal>
    @foreach (['masuk' => ['Surat Masuk', 'inbox'], 'keluar' => ['Surat Keluar', 'send'], 'disposisi' => ['Disposisi', 'git-pull-request-arrow']] as $k => [$l, $i])
        <div class="card p-5 flex items-center gap-4" data-testid="rekap-total-{{ $k }}">
            <span class="grid place-items-center w-12 h-12 rounded-2xl bg-ruby-50 text-maroon-800"><i data-lucide="{{ $i }}" class="w-5 h-5"></i></span>
            <div><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ $l }}</p><p class="font-display text-3xl font-extrabold text-maroon-950">{{ number_format($total($k)) }}</p></div>
        </div>
    @endforeach
</div>
<div class="card overflow-hidden" data-reveal>
    <div class="px-5 py-4 border-b border-slate-100 text-sm text-slate-500">Periode <b class="text-maroon-950">{{ $awal->translatedFormat('d F Y') }}</b> s.d. <b class="text-maroon-950">{{ $akhir->translatedFormat('d F Y') }}</b></div>
    <div class="overflow-x-auto">
        <table class="table-modern" data-testid="rekap-table">
            <thead><tr><th>No</th><th>Satuan Kerja</th><th class="text-right">Surat Masuk</th><th class="text-right">Surat Keluar</th><th class="text-right">Disposisi</th><th class="text-right">Total</th></tr></thead>
            <tbody>
                @forelse ($rows as $i => $r)
                    <tr data-testid="rekap-row-{{ $r->kode }}">
                        <td class="text-slate-400">{{ $loop->iteration }}</td>
                        <td><p class="font-semibold text-maroon-950">{{ $r->satker }}</p><p class="text-[11px] text-slate-400">{{ $r->kode }}</p></td>
                        <td class="text-right tabular-nums">{{ $r->masuk }}</td>
                        <td class="text-right tabular-nums">{{ $r->keluar }}</td>
                        <td class="text-right tabular-nums">{{ $r->disposisi }}</td>
                        <td class="text-right tabular-nums font-bold text-maroon-950">{{ $r->masuk + $r->keluar + $r->disposisi }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-slate-400 py-10">Belum ada satuan kerja.</td></tr>
                @endforelse
            </tbody>
            <tfoot><tr class="bg-ruby-50/50 font-bold text-maroon-950"><td></td><td class="px-5 py-3">TOTAL</td><td class="px-5 text-right">{{ $total('masuk') }}</td><td class="px-5 text-right">{{ $total('keluar') }}</td><td class="px-5 text-right">{{ $total('disposisi') }}</td><td class="px-5 text-right" data-testid="rekap-grand-total">{{ $total('masuk') + $total('keluar') + $total('disposisi') }}</td></tr></tfoot>
        </table>
    </div>
</div>
@endsection
