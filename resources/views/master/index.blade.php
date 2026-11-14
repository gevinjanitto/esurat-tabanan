@extends('layouts.app')
@section('title', $title)
@section('overline', 'Data Master')
@section('content')
@php
    $display = function ($row, $f) use ($satkers, $templates) {
        return match ($f) {
            'tanggal' => $row->tanggal?->translatedFormat('l, d F Y'),
            'template_surat_id' => $templates->firstWhere('id', $row->$f)?->nama ?? 'Semua jenis surat',
            'bulan' => \App\Models\NomorCadangan::BULAN[$row->bulan] ?? $row->bulan,
            'parent_id', 'satker_id' => $satkers->firstWhere('id', $row->$f)?->nama ?? '—',
            'is_pimpinan', 'status' => $row->$f ? 'Ya' : 'Tidak',
            'kategori' => \App\Models\Satker::KATEGORI[$row->$f] ?? $row->$f,
            default => $row->$f,
        };
    };
@endphp
<div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6" data-reveal>
    <form class="relative flex-1 max-w-md" method="GET"><i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i><input name="q" value="{{ request('q') }}" class="input pl-11 rounded-full" placeholder="Cari nama…" data-testid="master-search-input"></form>
    <button type="button" class="btn btn-primary sm:ml-auto" data-modal-open="master-modal-new" data-testid="master-add-button"><i data-lucide="plus" class="w-4 h-4"></i> Tambah {{ $title }}</button>
</div>
<div class="card overflow-hidden" data-reveal>
    <div class="overflow-x-auto">
        <table class="table-modern" data-testid="master-table">
            <thead><tr>@foreach ($fields as $l)<th>{{ $l }}</th>@endforeach<th class="text-right">Aksi</th></tr></thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr data-testid="master-row-{{ $row->id }}">
                        @foreach ($fields as $f => $l)<td class="{{ $loop->first ? 'font-semibold text-maroon-950' : 'text-slate-600' }}">@if ($f === 'warna')<x-badge :color="$row->warna">{{ $row->warna }}</x-badge>@else{{ $display($row, $f) }}@endif</td>@endforeach
                        <td class="text-right whitespace-nowrap">
                            @if ($type === 'satker')<a href="{{ route('satker.jabatan.index', $row->id) }}" class="btn btn-soft !py-1.5 !px-3 text-xs mr-1" data-testid="satker-jabatan-link-{{ $row->id }}"><i data-lucide="briefcase-business" class="w-3.5 h-3.5"></i> Jabatan</a>@endif
                            <button type="button" class="btn-icon text-slate-500 hover:bg-ruby-50 hover:text-maroon-700" data-modal-open="master-modal-{{ $row->id }}" data-testid="master-edit-{{ $row->id }}"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                            <form method="POST" action="{{ route('master.destroy', [$type, $row->id]) }}" class="inline" data-confirm="Hapus data ini?">@csrf @method('DELETE')<button class="btn-icon text-slate-500 hover:bg-rose-50 hover:text-rose-600" data-testid="master-delete-{{ $row->id }}"><i data-lucide="trash-2" class="w-4 h-4"></i></button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($fields) + 1 }}" class="text-center text-slate-400 py-10">Belum ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">{{ $rows->links() }}</div>
@endsection

@push('modals')
@foreach (collect([null])->merge($rows->items()) as $row)
    <div id="master-modal-{{ $row?->id ?? 'new' }}" class="modal-backdrop">
        <div class="modal-panel">
            <div class="flex items-center justify-between mb-5"><h3 class="font-display text-xl font-bold text-maroon-950">{{ $row ? 'Ubah' : 'Tambah' }} {{ $title }}</h3><button type="button" class="btn-icon hover:bg-slate-100" data-modal-close><i data-lucide="x" class="w-5 h-5"></i></button></div>
            <form method="POST" action="{{ $row ? route('master.update', [$type, $row->id]) : route('master.store', $type) }}" class="space-y-4" data-testid="master-form-{{ $row?->id ?? 'new' }}">
                @csrf @if ($row) @method('PUT') @endif
                @foreach ($fields as $f => $l)
                    <div>
                        <label class="label">{{ $l }}</label>
                        @if (in_array($f, ['parent_id', 'satker_id']))
                            <select name="{{ $f }}" class="input" data-testid="master-input-{{ $f }}-{{ $row?->id ?? 'new' }}"><option value="">—</option>@foreach ($satkers as $s)@continue($row && $type === 'satker' && $s->id === $row->id)<option value="{{ $s->id }}" @selected($row?->$f == $s->id)>{{ $s->nama }}</option>@endforeach</select>
                        @elseif ($f === 'is_pimpinan')
                            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_pimpinan" value="1" class="w-4 h-4 accent-ruby-600" @checked($row?->is_pimpinan)> Jabatan pimpinan</label>
                        @elseif ($f === 'status')
                            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="status" value="1" class="w-4 h-4 accent-ruby-600" @checked($row?->status ?? true) data-testid="master-input-status-{{ $row?->id ?? 'new' }}"> Aktif</label>
                        @elseif ($f === 'tanggal')
                            <input type="date" name="tanggal" value="{{ $row?->tanggal?->toDateString() }}" class="input" required data-testid="master-input-tanggal-{{ $row?->id ?? 'new' }}">
                        @elseif ($f === 'template_surat_id')
                            <select name="template_surat_id" class="input" data-testid="master-input-template_surat_id-{{ $row?->id ?? 'new' }}"><option value="">Semua jenis surat</option>@foreach ($templates as $t)<option value="{{ $t->id }}" @selected($row?->template_surat_id == $t->id)>{{ $t->nama }}</option>@endforeach</select>
                        @elseif ($f === 'bulan')
                            <select name="bulan" class="input" data-testid="master-input-bulan-{{ $row?->id ?? 'new' }}">@foreach (\App\Models\NomorCadangan::BULAN as $k => $b)<option value="{{ $k }}" @selected(($row?->bulan ?? now()->month) == $k)>{{ $b }}</option>@endforeach</select>
                        @elseif ($f === 'jumlah')
                            <input type="number" min="0" max="500" name="jumlah" value="{{ $row?->jumlah ?? 5 }}" class="input" required data-testid="master-input-jumlah-{{ $row?->id ?? 'new' }}">
                        @elseif (in_array($f, ['tahun', 'nomor_awal']))
                            <input type="number" name="{{ $f }}" value="{{ $row?->$f ?? ($f === 'tahun' ? now()->year : 1) }}" class="input" required data-testid="master-input-{{ $f }}-{{ $row?->id ?? 'new' }}">
                        @elseif ($f === 'kategori')
                            <select name="kategori" class="input" data-testid="master-input-kategori-{{ $row?->id ?? 'new' }}">@foreach (\App\Models\Satker::KATEGORI as $k => $v)<option value="{{ $k }}" @selected(($row?->kategori ?? 'divisi') === $k)>{{ $v }}</option>@endforeach</select>
                        @elseif ($f === 'warna')
                            <select name="warna" class="input">@foreach (['ruby', 'sky', 'amber', 'rose', 'slate'] as $w)<option @selected($row?->warna === $w)>{{ $w }}</option>@endforeach</select>
                        @else
                            <input name="{{ $f }}" value="{{ $row?->$f }}" class="input" required data-testid="master-input-{{ $f }}-{{ $row?->id ?? 'new' }}">
                        @endif
                    </div>
                @endforeach
                <button class="btn btn-primary w-full" data-testid="master-save-{{ $row?->id ?? 'new' }}"><i data-lucide="save" class="w-4 h-4"></i> Simpan</button>
            </form>
        </div>
    </div>
@endforeach
@endpush
