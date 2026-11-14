@extends('layouts.app')
@section('title', 'Transfer Surat')
@section('overline', 'Administrasi')
@section('content')
@php $opt = fn ($u) => $u->name . ($u->nrk ? " ({$u->nrk})" : '') . ' — ' . ($u->jabatan?->nama ?? $u->role_label) . ($u->is_active ? '' : ' [nonaktif]'); @endphp
<div class="flex gap-1 overflow-x-auto rounded-full bg-white border border-slate-200 p-1 w-fit mb-6" data-reveal>
    <a href="{{ route('transfer.index', 'disposisi') }}" class="tab {{ $jenis === 'disposisi' ? 'active' : '' }}" data-testid="transfer-tab-disposisi"><i data-lucide="git-pull-request-arrow" class="w-4 h-4 inline -mt-0.5 mr-1"></i>Disposisi Surat</a>
    <a href="{{ route('transfer.index', 'keluar') }}" class="tab {{ $jenis === 'keluar' ? 'active' : '' }}" data-testid="transfer-tab-keluar"><i data-lucide="send" class="w-4 h-4 inline -mt-0.5 mr-1"></i>Surat Keluar</a>
</div>
<form method="POST" action="{{ route('transfer.proses') }}" class="card p-6 space-y-5 max-w-3xl" data-reveal data-transfer-form data-confirm="Pindahkan surat ke pegawai tujuan? Tindakan ini tidak bisa dibatalkan otomatis." data-testid="transfer-form">
    @csrf
    <input type="hidden" name="jenis" value="{{ $jenis }}">
    <p class="text-sm text-slate-500">{{ $jenis === 'disposisi' ? 'Disposisi dan surat masuk yang diteruskan kepada pegawai asal akan dipindahkan ke pegawai tujuan.' : 'Surat keluar yang dibuat pegawai asal akan dipindahkan ke pegawai tujuan (beserta satkernya bila berbeda).' }}</p>
    <div class="grid sm:grid-cols-2 gap-4">
        <div><label class="label">Pegawai lama (asal)</label><select name="asal_id" class="input" required data-testid="transfer-asal-select"><option value="">— pilih —</option>@foreach ($users as $u)<option value="{{ $u->id }}" @selected(old('asal_id') == $u->id)>{{ $opt($u) }}</option>@endforeach</select></div>
        <div><label class="label">Pegawai baru (tujuan)</label><select name="tujuan_id" class="input" required data-testid="transfer-tujuan-select"><option value="">— pilih —</option>@foreach ($users->where('is_active', true) as $u)<option value="{{ $u->id }}" @selected(old('tujuan_id') == $u->id)>{{ $opt($u) }}</option>@endforeach</select></div>
        <div><label class="label">Tanggal awal</label><input type="date" name="tgl_awal" value="{{ old('tgl_awal', now()->startOfYear()->toDateString()) }}" class="input" required data-testid="transfer-tgl-awal"></div>
        <div><label class="label">Tanggal akhir</label><input type="date" name="tgl_akhir" value="{{ old('tgl_akhir', now()->toDateString()) }}" class="input" required data-testid="transfer-tgl-akhir"></div>
    </div>
    <div class="rounded-2xl bg-ruby-50/60 border border-ruby-100 px-4 py-3 text-sm text-maroon-900 hidden" data-transfer-result data-testid="transfer-count-result"></div>
    <div class="flex flex-wrap gap-3">
        <button type="button" class="btn btn-soft" data-transfer-hitung data-testid="transfer-hitung-button"><i data-lucide="calculator" class="w-4 h-4"></i> Hitung Jumlah Surat</button>
        <button class="btn btn-primary" data-testid="transfer-submit-button"><i data-lucide="arrow-right-left" class="w-4 h-4"></i> Transfer</button>
    </div>
</form>
@endsection
@push('scripts')
<script>
document.querySelector('[data-transfer-hitung]')?.addEventListener('click', async () => {
    const f = document.querySelector('[data-transfer-form]'), out = document.querySelector('[data-transfer-result]');
    const p = new URLSearchParams({ jenis: f.jenis.value, asal_id: f.asal_id.value, tgl_awal: f.tgl_awal.value, tgl_akhir: f.tgl_akhir.value });
    out.classList.remove('hidden');
    out.textContent = 'Menghitung…';
    try {
        const r = await fetch(@json(route('transfer.hitung')) + '?' + p, { headers: { Accept: 'application/json' } });
        const j = await r.json();
        if (!r.ok) { out.textContent = j.message || 'Lengkapi pegawai asal dan tanggal.'; return; }
        const label = { disposisi: 'disposisi', surat_diteruskan: 'surat masuk diteruskan', surat_keluar: 'surat keluar' };
        out.innerHTML = 'Akan dipindahkan: ' + Object.entries(j.jumlah).map(([k, n]) => `<b>${n}</b> ${label[k] || k}`).join(', ');
    } catch (e) { out.textContent = 'Gagal menghitung, coba lagi.'; }
});
</script>
@endpush
