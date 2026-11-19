@extends('layouts.app')
@section('title', 'Template Tujuan Surat')
@section('overline', 'Grup penerima surat keluar')
@section('content')
@php $names = $satkers->pluck('nama', 'id'); @endphp
<div class="flex items-center gap-3 mb-6" data-reveal>
    <p class="text-sm text-slate-500 max-w-xl">Kelompokkan satker tujuan & tembusan yang sering dipakai, lalu pilih sekali klik di form surat keluar.</p>
    <button type="button" class="btn btn-primary ml-auto" data-modal-open="tujuan-modal-new" data-testid="tujuan-add-button"><i data-lucide="plus" class="w-4 h-4"></i> Tambah Template</button>
</div>
<div class="card overflow-hidden" data-reveal>
    <div class="overflow-x-auto">
        <table class="table-modern" data-testid="tujuan-table">
            <thead><tr><th>Nama</th><th>Tujuan</th><th>Tembusan</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
                @forelse ($rows as $r)
                    <tr data-testid="tujuan-row-{{ $r->id }}">
                        <td class="font-semibold text-maroon-950 whitespace-nowrap">{{ $r->nama }}</td>
                        <td><div class="flex flex-wrap gap-1">@foreach ($r->tujuan as $id)<span class="badge badge-ruby">{{ $names[$id] ?? '-' }}</span>@endforeach</div></td>
                        <td><div class="flex flex-wrap gap-1">@forelse ($r->tembusan as $id)<span class="badge badge-slate">{{ $names[$id] ?? '-' }}</span>@empty<span class="text-slate-400">—</span>@endforelse</div></td>
                        <td class="text-right whitespace-nowrap">
                            <button type="button" class="btn-icon text-slate-500 hover:bg-ruby-50 hover:text-maroon-700" data-modal-open="tujuan-modal-{{ $r->id }}" data-testid="tujuan-edit-{{ $r->id }}"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                            <form method="POST" action="{{ route('template-tujuan.destroy', $r) }}" class="inline" data-confirm="Hapus template ini?">@csrf @method('DELETE')<button class="btn-icon text-slate-500 hover:bg-rose-50 hover:text-rose-600" data-testid="tujuan-delete-{{ $r->id }}"><i data-lucide="trash-2" class="w-4 h-4"></i></button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-slate-400 py-10">Belum ada template tujuan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('modals')
@foreach (collect([null])->merge($rows) as $r)
    <div id="tujuan-modal-{{ $r?->id ?? 'new' }}" class="modal-backdrop">
        <div class="modal-panel max-h-[90vh] overflow-y-auto scrollbar-thin">
            <div class="flex items-center justify-between mb-5"><h3 class="font-display text-xl font-bold text-maroon-950">{{ $r ? 'Ubah' : 'Tambah' }} Template Tujuan</h3><button type="button" class="btn-icon hover:bg-slate-100" data-modal-close><i data-lucide="x" class="w-5 h-5"></i></button></div>
            <form method="POST" action="{{ $r ? route('template-tujuan.update', $r) : route('template-tujuan.store') }}" class="space-y-4" data-testid="tujuan-form-{{ $r?->id ?? 'new' }}">
                @csrf @if ($r) @method('PUT') @endif
                <div><label class="label">Nama template *</label><input name="nama" value="{{ $r?->nama }}" class="input" required data-testid="tujuan-nama-input-{{ $r?->id ?? 'new' }}"></div>
                <div class="grid sm:grid-cols-2 gap-4">
                    @foreach (['tujuan' => 'Tujuan *', 'tembusan' => 'Tembusan'] as $jenis => $label)
                        <div>
                            <label class="label">{{ $label }}</label>
                            <div class="rounded-2xl border border-slate-200 max-h-56 overflow-y-auto scrollbar-thin p-2 space-y-0.5">
                                @foreach ($satkers as $s)
                                    <label class="flex items-center gap-2 rounded-xl px-2 py-1.5 text-sm hover:bg-ruby-50 cursor-pointer"><input type="checkbox" name="{{ $jenis }}[]" value="{{ $s->id }}" class="w-4 h-4 accent-ruby-600" @checked(in_array($s->id, $r?->$jenis ?? [])) data-testid="tujuan-{{ $jenis }}-{{ $r?->id ?? 'new' }}-{{ $s->id }}"> <span class="truncate">{{ $s->nama }}</span></label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                <button class="btn btn-primary w-full" data-testid="tujuan-save-{{ $r?->id ?? 'new' }}"><i data-lucide="save" class="w-4 h-4"></i> Simpan</button>
            </form>
        </div>
    </div>
@endforeach
@endpush
