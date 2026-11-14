@extends('layouts.app')
@section('title', 'Input Arsip Surat Keluar')
@section('overline', 'Surat keluar lama yang sudah ditandatangani')
@section('content')
<form method="POST" action="{{ route('surat-keluar.arsip.store') }}" enctype="multipart/form-data" class="grid xl:grid-cols-[1fr_380px] gap-6" data-testid="arsip-form">
    @csrf
    <section class="card p-6 sm:p-8 space-y-5" data-reveal>
        <div class="grid sm:grid-cols-2 gap-5">
            <div><label class="label">Nomor surat *</label><input name="nomor_surat" value="{{ old('nomor_surat') }}" class="input font-mono" required data-testid="arsip-nomor-input">@error('nomor_surat')<p class="error-text">{{ $message }}</p>@enderror</div>
            <div><label class="label">Tanggal surat *</label><input type="date" name="tanggal_surat" value="{{ old('tanggal_surat') }}" max="{{ now()->toDateString() }}" class="input" required data-testid="arsip-tanggal-input">@error('tanggal_surat')<p class="error-text">{{ $message }}</p>@enderror</div>
        </div>
        <div><label class="label">Perihal *</label><input name="perihal" value="{{ old('perihal') }}" class="input" required data-testid="arsip-perihal-input">@error('perihal')<p class="error-text">{{ $message }}</p>@enderror</div>
        <div><label class="label">Tujuan *</label><textarea name="tujuan" rows="2" class="input" required data-testid="arsip-tujuan-input">{{ old('tujuan') }}</textarea></div>
        <div><label class="label">Ringkasan</label><textarea name="ringkasan" rows="3" class="input" data-testid="arsip-ringkasan-input">{{ old('ringkasan') }}</textarea></div>
        <div class="grid sm:grid-cols-2 gap-5">
            <div><label class="label">Klasifikasi</label><select name="klasifikasi_id" class="input" data-testid="arsip-klasifikasi-select"><option value="">—</option>@foreach ($klasifikasis as $o)<option value="{{ $o->id }}" @selected(old('klasifikasi_id') == $o->id)>{{ $o->kode }} · {{ $o->nama }}</option>@endforeach</select></div>
            <div><label class="label">Sifat</label><select name="sifat_id" class="input" data-testid="arsip-sifat-select"><option value="">—</option>@foreach ($sifats as $o)<option value="{{ $o->id }}" @selected(old('sifat_id') == $o->id)>{{ $o->nama }}</option>@endforeach</select></div>
            <div><label class="label">Klasifikasi Keamanan</label><select name="klasifikasi_keamanan_id" class="input"><option value="">—</option>@foreach ($keamanans as $o)<option value="{{ $o->id }}">{{ $o->nama }}</option>@endforeach</select></div>
            <div><label class="label">Satker pembuat</label><select name="satker_id" class="input" data-testid="arsip-satker-select"><option value="">—</option>@foreach ($satkers as $o)<option value="{{ $o->id }}" @selected(old('satker_id', auth()->user()->satker_id) == $o->id)>{{ $o->nama }}</option>@endforeach</select></div>
            <div class="sm:col-span-2"><label class="label">Unit pemrakarsa</label><select name="unit_pemrakarsa_id" class="input"><option value="">—</option>@foreach ($unitPemrakarsas as $o)<option value="{{ $o->id }}">{{ $o->kode }} · {{ $o->nama }}</option>@endforeach</select></div>
        </div>
    </section>
    <aside class="space-y-6">
        <label class="card block p-8 border-2 border-dashed border-ruby-200 text-center cursor-pointer hover:border-ruby-400 transition-colors" data-dropzone data-reveal data-testid="arsip-file-dropzone">
            <input type="file" name="file" accept="application/pdf" class="sr-only" required data-testid="arsip-file-input">
            <span class="mx-auto grid place-items-center w-16 h-16 rounded-3xl bg-blush-100 text-maroon-700"><i data-lucide="archive" class="w-7 h-7"></i></span>
            <p class="mt-4 font-bold text-maroon-950">PDF surat (sudah bertanda tangan) *</p>
            <p class="text-xs text-slate-500 mt-1" data-file-name>Seret file atau ketuk untuk memilih (maks 20MB)</p>
            @error('file')<p class="error-text">{{ $message }}</p>@enderror
        </label>
        <section class="card p-6" data-reveal>
            <p class="text-xs text-slate-500 mb-4">Arsip tidak melewati alur verifikasi/TTE dan langsung tercatat pada Daftar Nomor.</p>
            <button class="btn btn-primary w-full" data-testid="arsip-save-button"><i data-lucide="archive" class="w-4 h-4"></i> Simpan Arsip</button>
        </section>
    </aside>
</form>
@endsection
