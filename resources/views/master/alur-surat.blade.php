@extends('layouts.app')
@section('title', 'Alur Surat')
@section('overline', 'Template alur verifikasi & tanda tangan')
@section('content')
@php $usersJson = $users->map(fn ($x) => ['id' => $x->id, 'name' => $x->name, 'jabatan' => $x->jabatan?->nama ?? $x->role_label])->values(); $names = $users->pluck('name', 'id'); @endphp
<div class="flex items-center gap-3 mb-6" data-reveal>
    <p class="text-sm text-slate-500 max-w-xl">Simpan susunan verifikator & penandatangan yang sering dipakai, lalu terapkan sekali klik di form surat keluar.</p>
    <button type="button" class="btn btn-primary ml-auto" data-modal-open="alur-modal-new" data-testid="alur-add-button"><i data-lucide="plus" class="w-4 h-4"></i> Tambah Alur</button>
</div>
@if ($rows->isEmpty())
    <x-empty title="Belum ada alur tersimpan" icon="route">Tambahkan alur pertama Anda.</x-empty>
@else
<div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5" data-reveal>
    @foreach ($rows as $r)
        <article class="card p-6" data-testid="alur-card-{{ $r->id }}">
            <div class="flex items-start justify-between gap-2"><h3 class="font-display text-lg font-bold text-maroon-950">{{ $r->judul }}</h3><span class="badge badge-slate">{{ ucfirst($r->metode_verifikasi) }}</span></div>
            @foreach (['verifikator' => 'Verifikator', 'penandatangan' => 'Penandatangan'] as $k => $l)
                <p class="mt-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $l }}</p>
                <ol class="mt-1.5 space-y-1">@forelse ($r->$k as $i => $id)<li class="text-sm text-slate-700"><span class="font-mono text-xs text-ruby-700">{{ $i + 1 }}.</span> {{ $names[$id] ?? 'Pengguna nonaktif' }}</li>@empty<li class="text-sm text-slate-400">—</li>@endforelse</ol>
            @endforeach
            <div class="mt-5 flex gap-2">
                <button type="button" class="btn btn-soft flex-1" data-modal-open="alur-modal-{{ $r->id }}" data-testid="alur-edit-{{ $r->id }}"><i data-lucide="pencil" class="w-4 h-4"></i> Ubah</button>
                <form method="POST" action="{{ route('alur-surat.destroy', $r) }}" data-confirm="Hapus alur ini?">@csrf @method('DELETE')<button class="btn btn-danger" data-testid="alur-delete-{{ $r->id }}"><i data-lucide="trash-2" class="w-4 h-4"></i></button></form>
            </div>
        </article>
    @endforeach
</div>
@endif
@endsection

@push('modals')
@foreach (collect([null])->merge($rows) as $r)
    <div id="alur-modal-{{ $r?->id ?? 'new' }}" class="modal-backdrop">
        <div class="modal-panel max-h-[90vh] overflow-y-auto scrollbar-thin">
            <div class="flex items-center justify-between mb-5"><h3 class="font-display text-xl font-bold text-maroon-950">{{ $r ? 'Ubah' : 'Tambah' }} Alur Surat</h3><button type="button" class="btn-icon hover:bg-slate-100" data-modal-close><i data-lucide="x" class="w-5 h-5"></i></button></div>
            <form method="POST" action="{{ $r ? route('alur-surat.update', $r) : route('alur-surat.store') }}" class="space-y-4" data-testid="alur-form-{{ $r?->id ?? 'new' }}">
                @csrf @if ($r) @method('PUT') @endif
                <div><label class="label">Judul alur *</label><input name="judul" value="{{ $r?->judul }}" class="input" required data-testid="alur-judul-input-{{ $r?->id ?? 'new' }}"></div>
                <div class="grid grid-cols-2 gap-2">
                    @foreach (['bersamaan' => 'Bersamaan', 'berjenjang' => 'Berjenjang'] as $k => $l)
                        <label class="cursor-pointer"><input type="radio" name="metode_verifikasi" value="{{ $k }}" class="peer sr-only" @checked(($r?->metode_verifikasi ?? 'bersamaan') === $k)><span class="block rounded-2xl border-2 border-slate-200 p-3 text-sm font-bold text-maroon-950 peer-checked:border-ruby-500 peer-checked:bg-ruby-50">{{ $l }}</span></label>
                    @endforeach
                </div>
                @foreach (['verifikator' => 'Verifikator', 'penandatangan' => 'Penandatangan *'] as $peran => $label)
                    <div data-alur-builder="{{ $peran }}" data-users='@json($usersJson)' data-selected='@json(collect($r?->$peran ?? [])->map(fn ($v) => (string) $v))'>
                        <label class="label">{{ $label }}</label>
                        <ul class="space-y-2 mb-2" data-alur-list></ul>
                        <select class="input" data-testid="alur-{{ $peran }}-select-{{ $r?->id ?? 'new' }}"><option value="">+ Tambah {{ strtolower(strtok($label, ' ')) }}</option>@foreach ($users as $x)<option value="{{ $x->id }}">{{ $x->name }} — {{ $x->jabatan?->nama ?? $x->role_label }}</option>@endforeach</select>
                    </div>
                @endforeach
                <button class="btn btn-primary w-full" data-testid="alur-save-{{ $r?->id ?? 'new' }}"><i data-lucide="save" class="w-4 h-4"></i> Simpan</button>
            </form>
        </div>
    </div>
@endforeach
@endpush
