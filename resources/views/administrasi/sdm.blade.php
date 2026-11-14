@extends('layouts.app')
@section('title', 'Data SDM')
@section('overline', 'Administrasi')
@section('content')
<div class="card p-5 mb-6 flex flex-col lg:flex-row lg:items-center gap-4" data-reveal>
    <div class="flex items-center gap-4 flex-1">
        <span class="grid place-items-center w-12 h-12 rounded-2xl bg-ruby-50 text-maroon-800"><i data-lucide="contact-round" class="w-5 h-5"></i></span>
        <div class="text-sm text-slate-600">
            <p><b class="text-maroon-950" data-testid="sdm-total">{{ number_format($total) }}</b> pegawai tersimpan · sinkron terakhir {{ $lastSync ? \Carbon\Carbon::parse($lastSync)->translatedFormat('d M Y H:i') : 'belum pernah' }}</p>
            <p class="text-xs text-slate-400 mt-0.5">Sumber: layanan HAIRISMA · jadwal otomatis setiap hari pukul <b>{{ $jam }}</b> (ubah di Setting Sistem)</p>
            @unless ($configured)<p class="text-xs text-amber-700 mt-1" data-testid="sdm-not-configured">Alamat HAIRISMA_URL belum diisi di file .env, sinkronisasi belum bisa dijalankan.</p>@endunless
        </div>
    </div>
    <form method="POST" action="{{ route('sdm.sync') }}" data-confirm="Sinkronkan data SDM sekarang?">@csrf<button class="btn btn-primary" data-testid="sdm-sync-button"><i data-lucide="refresh-ccw" class="w-4 h-4"></i> Sinkron Sekarang</button></form>
</div>
<form class="relative max-w-md mb-6" method="GET" data-reveal><i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i><input name="q" value="{{ request('q') }}" class="input pl-11 rounded-full" placeholder="Cari nama, NIP, atau unit…" data-testid="sdm-search-input"></form>
<div class="card overflow-hidden" data-reveal>
    <div class="overflow-x-auto">
        <table class="table-modern" data-testid="sdm-table">
            <thead><tr><th>No</th><th>Nama</th><th>NIP</th><th>Unit</th><th>Satuan Kerja</th><th>Sub Unit</th><th>Jabatan</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($rows as $r)
                    <tr data-testid="sdm-row-{{ $r->nrk }}">
                        <td class="text-slate-400">{{ $rows->firstItem() + $loop->index }}</td>
                        <td class="font-semibold text-maroon-950 whitespace-nowrap">{{ $r->nama }}</td>
                        <td class="font-mono text-xs">{{ $r->nrk }}</td>
                        <td class="text-slate-600">{{ $r->unit }}</td>
                        <td class="text-slate-600">{{ $r->satuan_kerja }}</td>
                        <td class="text-slate-600">{{ $r->sub_unit }}</td>
                        <td class="text-slate-600">{{ $r->jabatan }}</td>
                        <td>@if ($r->status_cuti)<span class="badge badge-amber">Cuti</span>@else<span class="badge badge-ruby">Aktif</span>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-slate-400 py-10">Belum ada data SDM. Tekan "Sinkron Sekarang".</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">{{ $rows->links() }}</div>
@endsection
