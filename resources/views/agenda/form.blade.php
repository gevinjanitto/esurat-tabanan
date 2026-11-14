@extends('layouts.app')
@section('title', $agenda->exists ? 'Ubah Agenda' : 'Tambah Agenda')
@section('overline', 'Agenda')
@section('content')
<form method="POST" action="{{ $agenda->exists ? route('agenda.update', $agenda) : route('agenda.store') }}" class="card max-w-3xl p-6 sm:p-8 space-y-5" data-reveal data-testid="agenda-form">
    @csrf @if ($agenda->exists) @method('PUT') @endif
    @php $sumber = old('sumber', $agenda->surat_masuk_id || $surat ? 'surat' : 'mandiri'); $suratId = old('surat_masuk_id', $agenda->surat_masuk_id ?? $surat?->id); @endphp
    <div data-testid="agenda-sumber-section">
        <label class="label">Apakah agenda ini dari surat masuk? *</label>
        <div class="grid grid-cols-2 gap-2">
            @foreach (['surat' => ['inbox', 'Ya, dari surat masuk'], 'mandiri' => ['calendar-plus', 'Tidak, agenda mandiri']] as $k => [$icon, $l])
                <label class="cursor-pointer"><input type="radio" name="sumber" value="{{ $k }}" class="peer sr-only" @checked($sumber === $k) data-agenda-sumber data-testid="agenda-sumber-{{ $k }}"><span class="flex items-center justify-center gap-2 rounded-2xl border-2 border-slate-200 p-3 text-sm font-bold text-maroon-950 transition-colors peer-checked:border-ruby-500 peer-checked:bg-ruby-50"><i data-lucide="{{ $icon }}" class="w-4 h-4"></i>{{ $l }}</span></label>
            @endforeach
        </div>
    </div>
    <div class="{{ $sumber === 'surat' ? '' : 'hidden' }}" data-agenda-surat-wrap>
        <label class="label">Surat masuk asal *</label>
        <select name="surat_masuk_id" class="input" data-testid="agenda-surat-select">
            <option value="">— Pilih surat masuk —</option>
            @foreach ($suratOptions as $so)<option value="{{ $so->id }}" @selected($suratId == $so->id)>{{ $so->nomor_surat }} · {{ \Illuminate\Support\Str::limit($so->perihal, 70) }}</option>@endforeach
            @if ($surat && !$suratOptions->contains('id', $surat->id))<option value="{{ $surat->id }}" selected>{{ $surat->nomor_surat }} · {{ $surat->perihal }}</option>@endif
        </select>
        @error('surat_masuk_id')<p class="error-text">{{ $message }}</p>@enderror
        @if ($agenda->disposisi_id)<p class="text-[11px] text-slate-400 mt-1">Agenda ini dibuat dari disposisi.</p>@endif
    </div>
    <div><label class="label">Judul agenda *</label><input name="judul" value="{{ old('judul', $agenda->judul) }}" class="input" required data-testid="agenda-judul-input">@error('judul')<p class="error-text">{{ $message }}</p>@enderror</div>
    <div class="grid sm:grid-cols-2 gap-5">
        <div><label class="label">Mulai *</label><input type="datetime-local" name="mulai" value="{{ old('mulai', $agenda->mulai?->format('Y-m-d\TH:i')) }}" class="input" required data-testid="agenda-mulai-input">@error('mulai')<p class="error-text">{{ $message }}</p>@enderror</div>
        <div><label class="label">Selesai</label><input type="datetime-local" name="selesai" value="{{ old('selesai', $agenda->selesai?->format('Y-m-d\TH:i')) }}" class="input" data-testid="agenda-selesai-input">@error('selesai')<p class="error-text">{{ $message }}</p>@enderror</div>
    </div>
    <div>
        <label class="label">Metode *</label>
        <div class="grid grid-cols-3 gap-2">
            @foreach (\App\Models\Agenda::METODE as $k => $l)
                <label class="cursor-pointer"><input type="radio" name="metode" value="{{ $k }}" class="peer sr-only" @checked(old('metode', $agenda->metode) === $k) data-testid="agenda-metode-{{ $k }}"><span class="block rounded-2xl border-2 border-slate-200 p-3 text-center text-sm font-bold text-maroon-950 transition-colors peer-checked:border-ruby-500 peer-checked:bg-ruby-50">{{ $l }}</span></label>
            @endforeach
        </div>
    </div>
    <div class="grid sm:grid-cols-2 gap-5">
        <div><label class="label">Tempat</label><input name="tempat" value="{{ old('tempat', $agenda->tempat) }}" class="input" placeholder="Ruang rapat lt. 3" data-testid="agenda-tempat-input">@error('tempat')<p class="error-text">{{ $message }}</p>@enderror</div>
        <div><label class="label">Tautan rapat</label><input type="url" name="link_meeting" value="{{ old('link_meeting', $agenda->link_meeting) }}" class="input" placeholder="https://" data-testid="agenda-link-input">@error('link_meeting')<p class="error-text">{{ $message }}</p>@enderror</div>
    </div>
    <div><label class="label">Keterangan</label><textarea name="deskripsi" rows="4" class="input" data-testid="agenda-deskripsi-input">{{ old('deskripsi', $agenda->deskripsi) }}</textarea></div>
    <div class="flex gap-2 pt-2"><button class="btn btn-primary" data-testid="agenda-save-button"><i data-lucide="save" class="w-4 h-4"></i> Simpan Agenda</button><a href="{{ $agenda->exists ? route('agenda.show', $agenda) : route('agenda.index') }}" class="btn btn-ghost">Batal</a></div>
</form>
@endsection
