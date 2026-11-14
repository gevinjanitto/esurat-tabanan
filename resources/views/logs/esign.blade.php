@extends('layouts.app')
@section('title', 'Catatan Aktivitas Esign')
@section('overline', 'Riwayat tanda tangan elektronik')
@section('content')
<form class="flex flex-col sm:flex-row gap-2 mb-6" method="GET" data-reveal>
    <div class="relative flex-1 max-w-md"><i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i><input name="q" value="{{ request('q') }}" class="input pl-11 rounded-full" placeholder="Cari dokumen atau penandatangan…" data-testid="esign-search-input"></div>
    <select name="status" class="input rounded-full sm:w-48" onchange="this.form.submit()" data-testid="esign-status-filter"><option value="">Semua hasil</option><option value="berhasil" @selected(request('status') === 'berhasil')>Berhasil</option><option value="gagal" @selected(request('status') === 'gagal')>Gagal</option></select>
</form>
<div class="card overflow-hidden" data-reveal>
    <div class="overflow-x-auto">
        <table class="table-modern" data-testid="esign-table">
            <thead><tr><th>Waktu</th><th>Penandatangan</th><th>Dokumen</th><th>Hasil</th><th>Keterangan</th><th>IP</th></tr></thead>
            <tbody>
                @forelse ($logs as $l)
                    <tr data-testid="esign-row-{{ $l->id }}">
                        <td class="text-xs whitespace-nowrap text-slate-500">{{ $l->created_at->translatedFormat('d M Y H:i:s') }}</td>
                        <td class="font-semibold whitespace-nowrap">{{ $l->user?->name ?? '—' }}</td>
                        <td class="min-w-56">@if ($l->surat && !$l->surat->trashed())<a href="{{ route('surat-keluar.show', $l->surat_keluar_id) }}" class="font-semibold text-maroon-950 hover:text-ruby-700">{{ $l->dokumen }}</a>@else{{ $l->dokumen }}@endif<p class="text-[11px] text-slate-400">{{ $l->surat?->perihal }}</p></td>
                        <td><span class="badge {{ $l->status === 'berhasil' ? 'badge-ruby' : 'badge-rose' }}">{{ ucfirst($l->status) }}</span></td>
                        <td class="text-slate-600 text-sm min-w-56">{{ $l->keterangan }}</td>
                        <td class="font-mono text-xs text-slate-400">{{ $l->ip }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-slate-400 py-10">Belum ada aktivitas tanda tangan elektronik.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">{{ $logs->links() }}</div>
@endsection
