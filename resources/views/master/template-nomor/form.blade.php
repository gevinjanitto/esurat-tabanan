@extends('layouts.app')
@section('title', $row->exists ? 'Ubah Template Nomor' : 'Tambah Template Nomor')
@section('overline', 'Template Nomor Surat')
@section('content')
@php
    $segments = collect(old('segments', $row->segments));
    $samples = \App\Models\TemplateNomor::samples();
@endphp
<form method="POST" action="{{ $row->exists ? route('template-nomor.update', $row) : route('template-nomor.store') }}" class="grid xl:grid-cols-[1fr_380px] gap-6" data-testid="template-nomor-form">
    @csrf @if ($row->exists) @method('PUT') @endif
    <div class="space-y-6 min-w-0">
        <section class="card p-6 sm:p-8 space-y-5" data-reveal>
            <div><label class="label">Nama template nomor *</label><input name="nama" value="{{ old('nama', $row->nama) }}" class="input" required data-testid="template-nomor-nama-input">@error('nama')<p class="error-text">{{ $message }}</p>@enderror</div>
            <div class="grid sm:grid-cols-2 gap-5">
                <div><label class="label">Naskah dinas (template surat)</label><select name="template_surat_id" class="input" data-testid="template-nomor-template-select"><option value="">Semua template</option>@foreach ($templates as $t)<option value="{{ $t->id }}" @selected(old('template_surat_id', $row->template_surat_id) == $t->id)>{{ $t->nama }}</option>@endforeach</select></div>
                <div><label class="label">Satker</label><select name="satker_id" class="input" data-testid="template-nomor-satker-select"><option value="">Semua satker</option>@foreach ($satkers as $s)<option value="{{ $s->id }}" @selected(old('satker_id', $row->satker_id) == $s->id)>{{ $s->nama }}</option>@endforeach</select></div>
                <div><label class="label">Digit nomor urut</label><input type="number" name="padding" min="1" max="8" value="{{ old('padding', $row->padding) }}" class="input" data-testid="template-nomor-padding-input"></div>
                <div><label class="label">Nomor tambahan (surat mundur)</label><select name="nomor_tambahan" class="input" data-testid="template-nomor-tambahan-select">@foreach (\App\Models\TemplateNomor::TAMBAHAN as $k => $v)<option value="{{ $k }}" @selected(old('nomor_tambahan', $row->nomor_tambahan) === $k)>{{ $v }}</option>@endforeach</select></div>
            </div>
        </section>
        <section class="card p-6" data-repeater data-nomor-builder data-samples='@json($samples)' data-free='@json(\App\Models\TemplateNomor::FREE_TEXT)' data-reveal data-testid="template-nomor-builder">
            <div class="flex items-center justify-between"><h3 class="font-display text-lg font-bold text-maroon-950">Susunan format</h3><button type="button" class="btn btn-soft !py-1.5 text-xs" data-repeater-add data-testid="template-nomor-segment-add"><i data-lucide="plus" class="w-3.5 h-3.5"></i> Bagian</button></div>
            <p class="text-xs text-slate-500 mt-1 mb-4">Urutkan bagian nomor; dipisahkan garis miring (/). Nomor Urut wajib ada.</p>
            @foreach (collect($errors->get('segments*'))->flatten() as $msg)
                <p class="error-text">{{ $msg }}</p>
            @endforeach
            <div class="space-y-2" data-repeater-list>
                @foreach ($segments as $seg)
                    @include('master.template-nomor._segment', ['seg' => $seg])
                @endforeach
            </div>
            <template>@include('master.template-nomor._segment', ['seg' => ['type' => 'tahun']])</template>
        </section>
    </div>
    <aside class="space-y-6">
        <section class="card p-6 bg-crimson text-white border-0" data-reveal>
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-ruby-300/80">Pratinjau nomor</p>
            <p class="mt-3 font-mono text-xl font-semibold break-all" data-nomor-preview data-testid="template-nomor-preview">—</p>
            <p class="mt-3 text-xs text-ruby-50/60">Contoh untuk nomor urut pertama pada bulan & tahun berjalan.</p>
        </section>
        <section class="card p-6 flex flex-col gap-3" data-reveal>
            <button class="btn btn-primary w-full" data-testid="template-nomor-save-button"><i data-lucide="save" class="w-4 h-4"></i> Simpan</button>
            <a href="{{ route('template-nomor.index') }}" class="btn btn-ghost w-full">Batal</a>
        </section>
    </aside>
</form>
@endsection
