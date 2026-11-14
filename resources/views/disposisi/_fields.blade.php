@php
    $d = $disposisi ?? null;
    $fid = $id ?? 'new';
    $selectedSubjek = $d ? collect(explode(', ', $d->instruksi))->map(fn ($x) => trim($x)) : collect();
    $subjekNames = $subjeks->pluck('nama');
    $freeText = $d ? $selectedSubjek->reject(fn ($x) => $subjekNames->contains($x))->join(', ') : '';
@endphp
@if (!$d)
    <div>
        <label class="label">Kepada *</label>
        @php $tujuanDisposisi = $tujuanDisposisi ?? collect(); @endphp
        <div class="flex gap-1.5 mb-2">
            <select class="input !py-2 text-xs flex-1" data-apply-tujuan-disposisi="#disposisi-penerima-{{ $fid }}" data-testid="disposisi-grup-select-{{ $fid }}"><option value="">Pilih grup penerima tersimpan…</option>@foreach ($tujuanDisposisi as $td)<option value="{{ $td->id }}" data-penerima='@json($td->penerima)'>{{ $td->nama }}</option>@endforeach</select>
            <button type="button" class="btn btn-ghost !py-1.5 !px-3 text-xs" data-save-tujuan-disposisi="#disposisi-penerima-{{ $fid }}" data-url="{{ route('tujuan-disposisi.store') }}" data-testid="disposisi-grup-save-{{ $fid }}" title="Simpan pilihan sebagai grup"><i data-lucide="bookmark-plus" class="w-3.5 h-3.5"></i></button>
        </div>
        <select id="disposisi-penerima-{{ $fid }}" name="ke_user_id[]" multiple size="5" class="input !bg-none scrollbar-thin" required data-testid="disposisi-penerima-select-{{ $fid }}">
            @foreach ($users as $usr)<option value="{{ $usr->id }}">{{ $usr->name }} — {{ $usr->jabatan?->nama ?? $usr->role_label }}</option>@endforeach
        </select>
        <p class="text-[11px] text-slate-400 mt-1">Tahan Ctrl/⌘ untuk memilih lebih dari satu.</p>
    </div>
@endif
<div>
    <label class="label">Subjek disposisi</label>
    <div class="flex flex-wrap gap-1.5" data-testid="disposisi-subjek-list-{{ $fid }}">
        @foreach ($subjeks as $sj)
            <label class="cursor-pointer"><input type="checkbox" name="subjek[]" value="{{ $sj->nama }}" class="peer sr-only" @checked($selectedSubjek->contains($sj->nama)) data-testid="disposisi-subjek-{{ $fid }}-{{ $sj->id }}"><span class="inline-block rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-600 transition-colors peer-checked:bg-maroon-900 peer-checked:border-maroon-900 peer-checked:text-white hover:border-ruby-300">{{ $sj->nama }}</span></label>
        @endforeach
    </div>
</div>
<div><label class="label">Instruksi lain</label><input name="instruksi" value="{{ $freeText }}" class="input" placeholder="Tulis bila tidak ada di subjek" data-testid="disposisi-instruksi-input-{{ $fid }}"></div>
<div><label class="label">Catatan</label><textarea name="catatan" rows="3" class="input" data-testid="disposisi-catatan-input-{{ $fid }}">{{ $d?->catatan }}</textarea></div>
<div><label class="label">Batas waktu</label><input type="date" name="batas_waktu" value="{{ $d?->batas_waktu?->format('Y-m-d') }}" class="input" data-testid="disposisi-batas-input-{{ $fid }}"></div>
@if (!$d && isset($surat))
    @php $isUndangan = \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($surat->perihal), ['undangan', 'rapat', 'pertemuan', 'acara']); @endphp
    <div class="rounded-2xl border border-gold-400/50 bg-[#fffbeb] p-4 space-y-3" data-agenda-toggle-wrap data-testid="disposisi-agenda-section">
        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" name="ke_agenda" value="1" class="mt-0.5 w-4 h-4 accent-ruby-600" @checked(old('ke_agenda', $isUndangan)) data-agenda-toggle data-testid="disposisi-ke-agenda-checkbox">
            <span><span class="block text-sm font-bold text-maroon-950">Masukkan ke Agenda</span><span class="block text-[11px] text-slate-500">{{ $isUndangan ? 'Surat ini terdeteksi sebagai undangan/jadwal rapat.' : 'Centang bila disposisi ini berisi jadwal rapat/kegiatan.' }} Agenda terlihat oleh semua pengguna.</span></span>
        </label>
        <div class="space-y-3 {{ old('ke_agenda', $isUndangan) ? '' : 'hidden' }}" data-agenda-fields>
            <div><label class="label">Judul agenda</label><input name="agenda_judul" value="{{ old('agenda_judul', $surat->perihal) }}" class="input" data-testid="disposisi-agenda-judul-input">@error('agenda_judul')<p class="error-text">{{ $message }}</p>@enderror</div>
            <div class="grid grid-cols-2 gap-2">
                <div><label class="label">Mulai</label><input type="datetime-local" name="agenda_mulai" value="{{ old('agenda_mulai', now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i')) }}" class="input" data-testid="disposisi-agenda-mulai-input">@error('agenda_mulai')<p class="error-text">{{ $message }}</p>@enderror</div>
                <div><label class="label">Selesai</label><input type="datetime-local" name="agenda_selesai" value="{{ old('agenda_selesai') }}" class="input" data-testid="disposisi-agenda-selesai-input">@error('agenda_selesai')<p class="error-text">{{ $message }}</p>@enderror</div>
            </div>
            <div><label class="label">Metode</label><select name="agenda_metode" class="input" data-testid="disposisi-agenda-metode-select">@foreach (\App\Models\Agenda::METODE as $k => $l)<option value="{{ $k }}" @selected(old('agenda_metode', 'offline') === $k)>{{ $l }}</option>@endforeach</select></div>
            <div><label class="label">Tempat</label><input name="agenda_tempat" value="{{ old('agenda_tempat') }}" class="input" placeholder="Ruang rapat lt. 3" data-testid="disposisi-agenda-tempat-input">@error('agenda_tempat')<p class="error-text">{{ $message }}</p>@enderror</div>
            <div><label class="label">Tautan rapat</label><input type="url" name="agenda_link" value="{{ old('agenda_link') }}" class="input" placeholder="https://" data-testid="disposisi-agenda-link-input">@error('agenda_link')<p class="error-text">{{ $message }}</p>@enderror</div>
        </div>
    </div>
@endif
