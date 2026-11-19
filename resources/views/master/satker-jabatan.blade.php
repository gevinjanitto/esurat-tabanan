@extends('layouts.app')
@section('title', 'Jabatan · ' . $satker->nama)
@section('overline', 'Struktur jabatan satuan kerja')
@section('content')
<div class="grid lg:grid-cols-[1fr_360px] gap-6">
    <div class="card overflow-hidden" data-reveal>
        <div class="flex items-center gap-3 p-5 border-b border-slate-100">
            <a href="{{ route('master.index', 'satker') }}" class="btn-icon btn-ghost" data-testid="satker-jabatan-back"><i data-lucide="arrow-left" class="w-4 h-4"></i></a>
            <div><p class="font-display text-lg font-bold text-maroon-950">{{ $satker->nama }}</p><p class="text-xs text-slate-500">{{ $satker->kode }} · {{ \App\Models\Satker::KATEGORI[$satker->kategori] ?? $satker->kategori }}</p></div>
        </div>
        <table class="table-modern" data-testid="satker-jabatan-table">
            <thead><tr><th>Jabatan</th><th>Pimpinan</th><th>Pemangku</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
                @forelse ($satker->jabatans as $j)
                    <tr data-testid="satker-jabatan-row-{{ $j->id }}">
                        <td>
                            <form method="POST" action="{{ route('satker.jabatan.update', [$satker, $j]) }}" class="flex items-center gap-2" id="jab-{{ $j->id }}">@csrf @method('PUT')
                                <input name="nama" value="{{ $j->nama }}" class="input !py-2 text-sm" required data-testid="satker-jabatan-nama-{{ $j->id }}">
                            </form>
                        </td>
                        <td><input type="checkbox" name="is_pimpinan" value="1" form="jab-{{ $j->id }}" class="w-4 h-4 accent-ruby-600" @checked($j->is_pimpinan)></td>
                        <td><span class="badge badge-slate">{{ $counts[$j->id] ?? 0 }} orang</span></td>
                        <td class="text-right whitespace-nowrap">
                            <button form="jab-{{ $j->id }}" class="btn-icon text-slate-500 hover:bg-ruby-50 hover:text-maroon-700" data-testid="satker-jabatan-save-{{ $j->id }}"><i data-lucide="save" class="w-4 h-4"></i></button>
                            <form method="POST" action="{{ route('satker.jabatan.destroy', [$satker, $j]) }}" class="inline" data-confirm="Hapus jabatan ini?">@csrf @method('DELETE')<button class="btn-icon text-slate-500 hover:bg-rose-50 hover:text-rose-600" data-testid="satker-jabatan-delete-{{ $j->id }}"><i data-lucide="trash-2" class="w-4 h-4"></i></button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-slate-400 py-10">Belum ada jabatan di satker ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <form method="POST" action="{{ route('satker.jabatan.store', $satker) }}" class="card p-6 space-y-4 h-fit" data-reveal data-testid="satker-jabatan-form">@csrf
        <p class="eyebrow">Tambah jabatan</p>
        <div><label class="label">Nama jabatan *</label><input name="nama" class="input" required data-testid="satker-jabatan-new-nama">@error('nama')<p class="error-text">{{ $message }}</p>@enderror</div>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_pimpinan" value="1" class="w-4 h-4 accent-ruby-600"> Jabatan pimpinan</label>
        <button class="btn btn-primary w-full" data-testid="satker-jabatan-add-button"><i data-lucide="plus" class="w-4 h-4"></i> Tambah</button>
    </form>
</div>
@endsection
