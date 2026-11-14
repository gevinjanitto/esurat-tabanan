@extends('layouts.app')
@section('title', $surat->exists ? 'Ubah Surat Masuk' : 'Catat Surat Masuk')
@section('overline', 'Surat Masuk')
@section('content')
<form method="POST" enctype="multipart/form-data" action="{{ $surat->exists ? route('surat-masuk.update', $surat) : route('surat-masuk.store') }}" class="grid lg:grid-cols-3 gap-6" data-testid="surat-masuk-form" data-confirm-modal="surat-confirm-modal">
    @csrf
    @if ($surat->exists) @method('PUT') @endif
    <div class="lg:col-span-2 card p-6 sm:p-8 space-y-5" data-reveal>
        <div class="grid sm:grid-cols-2 gap-5">
            <div><label class="label">Nomor Surat *</label><input name="nomor_surat" value="{{ old('nomor_surat', $surat->nomor_surat) }}" class="input" required data-confirm-label="Nomor surat" data-testid="surat-masuk-nomor-input">@error('nomor_surat')<p class="error-text">{{ $message }}</p>@enderror</div>
            <div><label class="label">Nomor Agenda <span class="text-slate-400 font-normal">(otomatis bila kosong)</span></label><input name="nomor_agenda" value="{{ old('nomor_agenda', $surat->nomor_agenda) }}" class="input" data-testid="surat-masuk-agenda-input"></div>
            <div><label class="label">Tanggal Surat *</label><input type="date" name="tanggal_surat" value="{{ old('tanggal_surat', optional($surat->tanggal_surat)->format('Y-m-d')) }}" class="input" required data-testid="surat-masuk-tanggal-surat-input"></div>
            <div><label class="label">Tanggal Diterima *</label><input type="date" name="tanggal_terima" value="{{ old('tanggal_terima', optional($surat->tanggal_terima)->format('Y-m-d')) }}" class="input" required data-testid="surat-masuk-tanggal-terima-input"></div>
        </div>
        <div><label class="label">Pengirim *</label><input name="pengirim" value="{{ old('pengirim', $surat->pengirim) }}" class="input" required data-confirm-label="Pengirim" data-testid="surat-masuk-pengirim-input"></div>
        <div><label class="label">Perihal *</label><input name="perihal" value="{{ old('perihal', $surat->perihal) }}" class="input" required data-confirm-label="Perihal" data-testid="surat-masuk-perihal-input">@error('perihal')<p class="error-text">{{ $message }}</p>@enderror</div>
        <div><label class="label">Ringkasan Isi</label><textarea name="ringkasan" rows="4" class="input" data-testid="surat-masuk-ringkasan-input">{{ old('ringkasan', $surat->ringkasan) }}</textarea></div>
        <div class="grid sm:grid-cols-3 gap-5">
            <div><label class="label">Sifat</label><select name="sifat_id" class="input" data-confirm-label="Sifat" data-testid="surat-masuk-sifat-select"><option value="">—</option>@foreach ($sifats as $o)<option value="{{ $o->id }}" @selected(old('sifat_id', $surat->sifat_id) == $o->id)>{{ $o->nama }}</option>@endforeach</select></div>
            <div><label class="label">Klasifikasi</label><select name="klasifikasi_id" class="input" data-testid="surat-masuk-klasifikasi-select"><option value="">—</option>@foreach ($klasifikasis as $o)<option value="{{ $o->id }}" @selected(old('klasifikasi_id', $surat->klasifikasi_id) == $o->id)>{{ $o->kode }} · {{ $o->nama }}</option>@endforeach</select></div>
            <div><label class="label">Keamanan</label><select name="klasifikasi_keamanan_id" class="input" data-testid="surat-masuk-keamanan-select"><option value="">—</option>@foreach ($keamanans as $o)<option value="{{ $o->id }}" @selected(old('klasifikasi_keamanan_id', $surat->klasifikasi_keamanan_id) == $o->id)>{{ $o->nama }}</option>@endforeach</select></div>
        </div>
    </div>
    <div class="space-y-6" data-reveal>
        <label class="card block p-6 border-2 border-dashed border-ruby-200 text-center cursor-pointer hover:border-ruby-400 transition-colors" data-dropzone data-testid="surat-masuk-file-dropzone">
            <input type="file" name="file" accept="application/pdf" class="sr-only" {{ $surat->exists ? '' : 'required' }} data-testid="surat-masuk-file-input">
            <span class="mx-auto grid place-items-center w-16 h-16 rounded-3xl bg-blush-100 text-maroon-700 animate-floaty"><i data-lucide="file-up" class="w-7 h-7"></i></span>
            <p class="mt-4 font-bold text-maroon-950">Unggah PDF surat</p>
            <p class="text-xs text-slate-500 mt-1" data-file-name>{{ $surat->file_name ?: 'Seret file ke sini atau ketuk untuk memilih (maks 20MB)' }}</p>
            @error('file')<p class="error-text">{{ $message }}</p>@enderror
        </label>
        <div class="card p-6 flex flex-col gap-3">
            <button class="btn btn-primary w-full" data-testid="surat-masuk-save-button"><i data-lucide="save" class="w-4 h-4"></i> Simpan</button>
            <a href="{{ $surat->exists ? route('surat-masuk.show', $surat) : route('surat-masuk.index') }}" class="btn btn-ghost w-full" data-testid="surat-masuk-cancel-button">Batal</a>
        </div>
    </div>
</form>
@endsection

@push('modals')
@include('partials.confirm-surat-modal')
@endpush
