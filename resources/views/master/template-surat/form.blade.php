@extends('layouts.app')
@section('title', $template->exists ? 'Ubah Template Surat' : 'Tambah Template Surat')
@section('overline', 'Template Surat')
@section('content')
@php
    $isians = collect(old('isian', $template->exists ? $template->isians->map->only(['nama', 'kode', 'jenis'])->all() : [['nama' => 'Nama Penerima', 'kode' => 'NAMA_PENERIMA', 'jenis' => 'teks']]));
@endphp
<form method="POST" action="{{ $template->exists ? route('template-surat.update', $template) : route('template-surat.store') }}" class="grid xl:grid-cols-[1fr_380px] gap-6" data-testid="template-surat-form">
    @csrf @if ($template->exists) @method('PUT') @endif
    <div class="space-y-6 min-w-0">
        <section class="card p-6 sm:p-8 space-y-5" data-reveal>
            <div class="grid sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2"><label class="label">Nama template *</label><input name="nama" value="{{ old('nama', $template->nama) }}" maxlength="200" class="input" required data-testid="template-nama-input">@error('nama')<p class="error-text">{{ $message }}</p>@enderror</div>
                <div><label class="label">Counter penomoran *</label><select name="counter_penomoran" class="input" data-testid="template-counter-select">@foreach (\App\Models\TemplateSurat::COUNTER as $k => $v)<option value="{{ $k }}" @selected(old('counter_penomoran', $template->counter_penomoran) === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="flex items-end"><label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700 pb-3"><input type="checkbox" name="status" value="1" class="w-4 h-4 accent-ruby-600" @checked(old('status', $template->status)) data-testid="template-status-checkbox"> Template aktif</label></div>
                <div class="sm:col-span-2"><label class="label">Deskripsi</label><textarea name="deskripsi" rows="2" class="input" data-testid="template-deskripsi-input">{{ old('deskripsi', $template->deskripsi) }}</textarea></div>
            </div>
        </section>
        @foreach ([['kop', 'Kop surat', 'Bagian kepala surat (logo/nama instansi/alamat). Boleh dikosongkan.'], ['isi', 'Isi surat *', 'Gunakan kode isian seperti ${NAMA_PENERIMA} atau kode sistem ${SYS_NO_SURAT}.']] as [$name, $label, $hint])
            <section class="card p-6" data-reveal>
                <h3 class="font-display text-lg font-bold text-maroon-950">{{ $label }}</h3>
                <p class="text-xs text-slate-500 mt-1 mb-3">{{ $hint }}</p>
                <div data-rich-editor data-testid="template-{{ $name }}-editor">
                    <div class="flex flex-wrap gap-1 rounded-t-2xl border border-b-0 border-slate-200 bg-slate-50 p-1.5">
                        @foreach ([['bold', 'bold'], ['italic', 'italic'], ['underline', 'underline'], ['justifyLeft', 'align-left'], ['justifyCenter', 'align-center'], ['justifyRight', 'align-right'], ['justifyFull', 'align-justify'], ['insertUnorderedList', 'list'], ['insertOrderedList', 'list-ordered'], ['formatBlock:h2', 'heading-2'], ['formatBlock:p', 'pilcrow'], ['removeFormat', 'eraser']] as [$cmd, $ic])
                            <button type="button" class="btn-icon !p-2 text-slate-600 hover:bg-white hover:text-maroon-800" data-cmd="{{ $cmd }}" title="{{ $cmd }}"><i data-lucide="{{ $ic }}" class="w-4 h-4"></i></button>
                        @endforeach
                    </div>
                    <div data-rich-area contenteditable="true" class="rich-area {{ $name === 'kop' ? 'min-h-28' : 'min-h-80' }}" data-testid="template-{{ $name }}-area"></div>
                    <textarea name="{{ $name }}" class="hidden" data-testid="template-{{ $name }}-input">{{ old($name, $template->$name) }}</textarea>
                </div>
                @error($name)<p class="error-text">{{ $message }}</p>@enderror
            </section>
        @endforeach
    </div>
    <aside class="space-y-6">
        <section class="card p-6" data-repeater data-reveal data-testid="template-isian-panel">
            <div class="flex items-center justify-between"><h3 class="font-display text-lg font-bold text-maroon-950">Isian template</h3><button type="button" class="btn btn-soft !py-1.5 text-xs" data-repeater-add data-testid="template-isian-add"><i data-lucide="plus" class="w-3.5 h-3.5"></i> Isian</button></div>
            <p class="text-xs text-slate-500 mt-1 mb-4">Field yang diisi pegawai saat membuat surat. Klik kode untuk menyisipkan ke editor.</p>
            @foreach (collect($errors->get('isian.*'))->flatten() as $msg)
                <p class="error-text">{{ $msg }}</p>
            @endforeach
            <div class="space-y-3" data-repeater-list>
                @foreach ($isians as $row)
                    @include('master.template-surat._isian', ['row' => $row])
                @endforeach
            </div>
            <template>@include('master.template-surat._isian', ['row' => ['nama' => '', 'kode' => '', 'jenis' => 'teks']])</template>
        </section>
        <section class="card p-6" data-reveal data-testid="template-sys-codes">
            <p class="eyebrow">Kode isian sistem</p>
            <p class="text-xs text-slate-500 mt-1 mb-3">Terisi otomatis dari data surat.</p>
            <div class="space-y-1.5">
                @foreach (\App\Models\TemplateSurat::SYS as $k => $v)
                    <button type="button" class="w-full flex items-center justify-between gap-2 rounded-xl bg-slate-50 px-3 py-2 text-left hover:bg-ruby-50" data-insert-code="{{ $k }}" data-testid="sys-code-{{ strtolower($k) }}"><span class="font-mono text-xs font-semibold text-maroon-800">${{ '{' . $k . '}' }}</span><span class="text-[11px] text-slate-500">{{ $v }}</span></button>
                @endforeach
            </div>
        </section>
        <section class="card p-6 flex flex-col gap-3" data-reveal>
            <button class="btn btn-primary w-full" data-testid="template-save-button"><i data-lucide="save" class="w-4 h-4"></i> Simpan Template</button>
            <a href="{{ route('template-surat.index') }}" class="btn btn-ghost w-full">Batal</a>
        </section>
    </aside>
</form>
@endsection
