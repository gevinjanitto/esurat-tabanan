@extends('layouts.app')
@section('title', $surat->exists ? 'Ubah Surat Keluar' : 'Buat Surat Keluar')
@section('overline', $surat->exists && $surat->status >= 11 ? 'Revisi ke-' . ($surat->revisi + 1) : 'Konsep Surat')
@section('content')
@php
    $usersJson = $users->map(fn ($x) => ['id' => $x->id, 'name' => $x->name, 'jabatan' => $x->jabatan?->nama ?? $x->role_label])->values();
    $draft = $draft ?? null;
    $sel = fn ($peran) => old($peran, $draft->data[$peran] ?? ($surat->exists ? $surat->alurs->where('peran', $peran)->sortBy('urut')->pluck('user_id')->values() : []));
    $draftIsian = collect($draft->data ?? [])->filter(fn ($v, $k) => str_starts_with($k, 'isian['))->mapWithKeys(fn ($v, $k) => [substr($k, 6, -1) => $v]);
    $isianValues = old('isian', $draftIsian->isNotEmpty() ? $draftIsian->all() : ($surat->isian ?? []));
    $tplId = old('template_surat_id', $draft->data['template_surat_id'] ?? $surat->template_surat_id);
    $templatesJson = $templates->map(fn ($t) => ['id' => $t->id, 'nama' => $t->nama, 'deskripsi' => $t->deskripsi, 'isians' => $t->isians->map->only(['nama', 'kode', 'jenis'])->values()])->values();
    $tujuanSel = fn ($jenis) => collect(old($jenis . '_satker', $draft->data[$jenis . '_satker'] ?? ($surat->exists ? $surat->tujuans->where('jenis', $jenis)->pluck('satker_id') : [])))->map(fn ($v) => (int) $v)->all();
@endphp
<form method="POST" enctype="multipart/form-data" action="{{ $surat->exists ? route('surat-keluar.update', $surat) : route('surat-keluar.store') }}" class="grid xl:grid-cols-[1fr_420px] gap-6" data-testid="surat-keluar-form" data-confirm-modal="surat-confirm-modal" data-autosave-url="{{ route('surat-keluar.autosave') }}" data-autosave-key="{{ $surat->exists ? $surat->id : 'baru' }}">
    @csrf
    @if ($surat->exists) @method('PUT') @endif
    <div class="space-y-6 min-w-0">
        @if ($draft)
            <div class="card p-4 flex flex-wrap items-center gap-3 border-gold-400/50 bg-[#fffbeb]" data-testid="autosave-restored-banner">
                <i data-lucide="history" class="w-5 h-5 text-gold-600"></i>
                <p class="text-sm text-slate-700 flex-1">Draft otomatis dipulihkan (tersimpan {{ $draft->updated_at->diffForHumans() }}). File PDF & lampiran perlu dipilih ulang.</p>
                <button type="submit" form="discard-draft-form" class="btn btn-ghost !py-1.5 text-xs" data-testid="autosave-discard-button"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Buang draft</button>
            </div>
        @endif
        <section class="card p-6 sm:p-8 space-y-4" data-reveal data-testid="surat-keluar-metode-ttd-section">
            <h3 class="font-display text-lg font-bold text-maroon-950 flex items-center gap-2"><i data-lucide="pen-tool" class="w-4 h-4 text-ruby-600"></i> Metode Tandatangan</h3>
            <div class="grid sm:grid-cols-2 gap-3">
                @foreach (['elektronik' => ['signature', 'QR & blok TTE dibubuhkan otomatis pada PDF'], 'basah' => ['pen-line', 'Penandatangan mencetak, tanda tangan, lalu unggah scan']] as $k => [$icon, $d])
                    <label class="relative cursor-pointer">
                        <input type="radio" name="metode_ttd" value="{{ $k }}" class="peer sr-only" @checked(old('metode_ttd', $draft->data['metode_ttd'] ?? $surat->metode_ttd ?? 'elektronik') === $k) data-confirm-label="Metode tandatangan" data-testid="metode-ttd-{{ $k }}-radio">
                        <span class="flex items-start gap-3 rounded-2xl border-2 border-slate-200 p-4 transition-colors peer-checked:border-ruby-500 peer-checked:bg-ruby-50"><i data-lucide="{{ $icon }}" class="w-5 h-5 text-ruby-700 shrink-0 mt-0.5"></i><span><span class="block text-sm font-bold text-maroon-950">{{ \App\Models\SuratKeluar::METODE_TTD[$k] }}</span><span class="block text-[11px] text-slate-500">{{ $d }}</span></span></span>
                    </label>
                @endforeach
            </div>
            @error('metode_ttd')<p class="error-text">{{ $message }}</p>@enderror
        </section>
        <section class="card p-6 sm:p-8 space-y-4" data-reveal data-testid="surat-keluar-template-section">
            <div class="flex flex-wrap items-center justify-between gap-2"><h3 class="font-display text-lg font-bold text-maroon-950 flex items-center gap-2"><i data-lucide="file-type-2" class="w-4 h-4 text-ruby-600"></i> Sumber dokumen</h3><span class="text-[11px] text-slate-400">Unggah PDF sendiri atau buat dari template</span></div>
            <select name="template_surat_id" class="input" data-template-select data-templates='@json($templatesJson)' data-values='@json((object) $isianValues)' data-testid="surat-keluar-template-select">
                <option value="">Unggah PDF konsep sendiri</option>
                @foreach ($templates as $t)<option value="{{ $t->id }}" @selected($tplId == $t->id)>Template: {{ $t->nama }}</option>@endforeach
            </select>
            <div class="hidden space-y-4" data-template-panel>
                <p class="text-xs text-slate-500" data-template-desc></p>
                <div class="grid sm:grid-cols-2 gap-4" data-template-fields></div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div><label class="label">Keterangan lampiran <span class="font-mono text-[10px] text-slate-400">${SYS_LAMPIRAN}</span></label><input name="lampiran_keterangan" value="{{ old('lampiran_keterangan', $draft->data['lampiran_keterangan'] ?? $surat->lampiran_keterangan) }}" class="input" placeholder="mis. 1 (satu) berkas" data-testid="surat-keluar-lampiran-keterangan-input"></div>
                    <div class="flex items-end"><button type="submit" formaction="{{ route('surat-keluar.template.preview') }}" formtarget="_blank" formnovalidate class="btn btn-gold w-full" data-testid="surat-keluar-template-preview-button"><i data-lucide="eye" class="w-4 h-4"></i> Pratinjau PDF</button></div>
                </div>
                <p class="text-[11px] text-slate-400">PDF dibuat otomatis dari template saat disimpan; nomor surat terisi saat diajukan.</p>
            </div>
        </section>
        <section class="card p-6 sm:p-8 space-y-5" data-reveal>
            <div class="flex flex-wrap items-center justify-between gap-2"><h3 class="font-display text-lg font-bold text-maroon-950">Informasi surat</h3><span class="text-[11px] font-semibold text-slate-400 flex items-center gap-1.5" data-testid="autosave-status"><i data-lucide="cloud" class="w-3.5 h-3.5"></i><span data-autosave-status>Draft disimpan otomatis saat Anda mengetik</span></span></div>
            <div><label class="label">Perihal *</label><input name="perihal" value="{{ old('perihal', $surat->perihal) }}" class="input" required data-confirm-label="Perihal" data-testid="surat-keluar-perihal-input">@error('perihal')<p class="error-text">{{ $message }}</p>@enderror</div>
            <div><label class="label">Tujuan (teks pada surat)</label><textarea name="tujuan" rows="2" class="input" placeholder="Kosongkan bila hanya ke satker internal" data-testid="surat-keluar-tujuan-input">{{ old('tujuan', $surat->tujuan) }}</textarea>@error('tujuan')<p class="error-text">{{ $message }}</p>@enderror</div>
            @if ($tujuanTemplates->isNotEmpty())
                <select class="input" data-apply-tujuan data-testid="surat-keluar-apply-tujuan-select"><option value="">Gunakan template tujuan surat…</option>@foreach ($tujuanTemplates as $tt)<option value="{{ $tt->id }}" data-tujuan='@json(['tujuan' => $tt->tujuan, 'tembusan' => $tt->tembusan])'>{{ $tt->nama }}</option>@endforeach</select>
            @endif
            <div class="grid sm:grid-cols-2 gap-5">
                @foreach (['tujuan' => 'Tujuan internal (satker)', 'tembusan' => 'Tembusan internal'] as $jenis => $jl)
                    <div>
                        <label class="label">{{ $jl }}</label>
                        <select name="{{ $jenis }}_satker[]" class="input" data-confirm-label="{{ $jl }}" data-testid="surat-keluar-{{ $jenis }}-satker-select">
                            <option value="">— Pilih satker —</option>
                            @foreach ($satkers as $o)<option value="{{ $o->id }}" @selected(in_array($o->id, array_slice($tujuanSel($jenis), 0, 1)))>{{ $o->nama }}</option>@endforeach
                        </select>
                    </div>
                @endforeach
                <p class="sm:col-span-2 -mt-2 text-[11px] text-slate-400">Setelah semua tanda tangan selesai, surat otomatis terkirim ke surat masuk satker yang dipilih.</p>
            </div>
            <div><label class="label">Ringkasan</label><textarea name="ringkasan" rows="3" class="input" data-testid="surat-keluar-ringkasan-input">{{ old('ringkasan', $surat->ringkasan) }}</textarea></div>
            <div class="grid sm:grid-cols-2 gap-5">
                <div><label class="label">Klasifikasi</label><select name="klasifikasi_id" class="input" data-testid="surat-keluar-klasifikasi-select"><option value="">—</option>@foreach ($klasifikasis as $o)<option value="{{ $o->id }}" @selected(old('klasifikasi_id', $surat->klasifikasi_id) == $o->id)>{{ $o->kode }} · {{ $o->nama }}</option>@endforeach</select></div>
                <div><label class="label">Sifat</label><select name="sifat_id" class="input" data-testid="surat-keluar-sifat-select"><option value="">—</option>@foreach ($sifats as $o)<option value="{{ $o->id }}" @selected(old('sifat_id', $surat->sifat_id) == $o->id)>{{ $o->nama }}</option>@endforeach</select></div>
                <div><label class="label">Klasifikasi Keamanan</label><select name="klasifikasi_keamanan_id" class="input" data-testid="surat-keluar-keamanan-select"><option value="">—</option>@foreach ($keamanans as $o)<option value="{{ $o->id }}" @selected(old('klasifikasi_keamanan_id', $surat->klasifikasi_keamanan_id) == $o->id)>{{ $o->nama }}</option>@endforeach</select></div>
                <div><label class="label">Satker pembuat</label><select name="satker_id" class="input" data-testid="surat-keluar-satker-select"><option value="">—</option>@foreach ($satkers as $o)<option value="{{ $o->id }}" @selected(old('satker_id', $surat->satker_id) == $o->id)>{{ $o->nama }}</option>@endforeach</select></div>
                <div><label class="label">Unit Pemrakarsa</label><select name="unit_pemrakarsa_id" class="input" data-testid="surat-keluar-unit-pemrakarsa-select"><option value="">—</option>@foreach ($unitPemrakarsas as $o)<option value="{{ $o->id }}" @selected(old('unit_pemrakarsa_id', $surat->unit_pemrakarsa_id) == $o->id)>{{ $o->kode }} · {{ $o->nama }}</option>@endforeach</select></div>
                <div><label class="label">Template nomor</label><select name="template_nomor_id" class="input" data-testid="surat-keluar-template-nomor-select"><option value="">Otomatis sesuai template & satker</option>@foreach ($templateNomors as $o)<option value="{{ $o->id }}" @selected(old('template_nomor_id', $surat->template_nomor_id) == $o->id)>{{ $o->nama }} ({{ $o->sample }})</option>@endforeach</select></div>
            </div>
        </section>
        <label data-upload-box class="card block p-8 border-2 border-dashed border-ruby-200 text-center cursor-pointer hover:border-ruby-400 transition-colors" data-dropzone data-reveal data-testid="surat-keluar-file-dropzone">
            <input type="file" name="file" accept="application/pdf" class="sr-only" data-required="{{ $surat->exists ? 0 : 1 }}" {{ $surat->exists ? '' : 'required' }} data-testid="surat-keluar-file-input">
            <span class="mx-auto grid place-items-center w-16 h-16 rounded-3xl bg-blush-100 text-maroon-700 animate-floaty"><i data-lucide="file-up" class="w-7 h-7"></i></span>
            <p class="mt-4 font-bold text-maroon-950">{{ $surat->exists ? 'Ganti PDF konsep (opsional)' : 'Unggah PDF konsep surat *' }}</p>
            <p class="text-xs text-slate-500 mt-1" data-file-name>{{ $surat->file_name ?: 'Seret file ke sini atau ketuk untuk memilih (maks 20MB)' }}</p>
            @error('file')<p class="error-text">{{ $message }}</p>@enderror
        </label>
        <section class="card p-6" data-reveal>
            <h3 class="font-display text-lg font-bold text-maroon-950 flex items-center gap-2"><i data-lucide="paperclip" class="w-4 h-4 text-ruby-600"></i> Lampiran</h3>
            <p class="text-xs text-slate-500 mt-1 mb-4">Boleh lebih dari satu (PDF, Word, Excel, gambar, ZIP · maks 20MB/berkas).</p>
            @if ($surat->exists && $surat->lampirans->isNotEmpty())
                <ul class="space-y-2 mb-4">@foreach ($surat->lampirans as $l)<li class="flex items-center gap-2 rounded-2xl bg-slate-50 px-3 py-2 text-sm"><i data-lucide="file" class="w-4 h-4 text-slate-400"></i><span class="truncate flex-1">{{ $l->file_name }}</span><span class="text-[11px] text-slate-400">{{ number_format($l->size / 1024, 0) }} KB</span></li>@endforeach</ul>
            @endif
            <input type="file" name="lampiran[]" multiple class="input file:mr-3 file:rounded-full file:border-0 file:bg-ruby-50 file:px-4 file:py-1.5 file:text-ruby-800 file:font-semibold" data-testid="surat-keluar-lampiran-input">
            @error('lampiran.*')<p class="error-text">{{ $message }}</p>@enderror
        </section>
    </div>

    <aside class="space-y-6">
        <section class="card p-6" data-reveal>
            <h3 class="font-display text-lg font-bold text-maroon-950">Alur surat</h3>
            <p class="text-xs text-slate-500 mt-1 mb-4">Atur urutan verifikator dan penandatangan.</p>
            @if ($alurTemplates->isNotEmpty())
                <select class="input mb-4" data-apply-alur data-testid="surat-keluar-apply-alur-select"><option value="">Gunakan alur tersimpan…</option>@foreach ($alurTemplates as $at)<option value="{{ $at->id }}" data-alur='@json(['metode_verifikasi' => $at->metode_verifikasi, 'verifikator' => $at->verifikator, 'penandatangan' => $at->penandatangan])'>{{ $at->judul }}</option>@endforeach</select>
            @endif
            <label class="label">Metode verifikasi</label>
            <div class="grid grid-cols-2 gap-2 mb-5">
                @foreach (['bersamaan' => ['Bersamaan', 'Semua verifikator sekaligus'], 'berjenjang' => ['Berjenjang', 'Satu per satu sesuai urutan']] as $k => [$l, $d])
                    <label class="relative cursor-pointer">
                        <input type="radio" name="metode_verifikasi" value="{{ $k }}" class="peer sr-only" @checked(old('metode_verifikasi', $surat->metode_verifikasi) === $k) data-testid="metode-{{ $k }}-radio">
                        <span class="block rounded-2xl border-2 border-slate-200 p-3 transition-colors peer-checked:border-ruby-500 peer-checked:bg-ruby-50"><span class="block text-sm font-bold text-maroon-950">{{ $l }}</span><span class="block text-[11px] text-slate-500">{{ $d }}</span></span>
                    </label>
                @endforeach
            </div>
            @foreach (['verifikator' => 'Verifikator (paraf)', 'penandatangan' => 'Penandatangan *'] as $peran => $label)
                <div class="mb-5" data-alur-builder="{{ $peran }}" data-users='@json($usersJson)' data-selected='@json($sel($peran))'>
                    <label class="label">{{ $label }}</label>
                    <ul class="space-y-2 mb-2" data-alur-list data-confirm-list="{{ $peran === 'penandatangan' ? 'Penandatangan' : 'Verifikator' }}"></ul>
                    <select class="input" data-testid="alur-{{ $peran }}-select"><option value="">+ Tambah {{ strtolower(strtok($label, ' ')) }}</option>@foreach ($users as $x)<option value="{{ $x->id }}">{{ $x->name }} — {{ $x->jabatan?->nama ?? $x->role_label }}</option>@endforeach</select>
                    @error($peran)<p class="error-text">{{ $message }}</p>@enderror
                </div>
            @endforeach
        </section>
        <section class="card p-6 flex flex-col gap-3" data-reveal>
            <button name="action" value="ajukan" class="btn btn-primary w-full" data-magnetic data-testid="surat-keluar-submit-button"><i data-lucide="send" class="w-4 h-4"></i> Simpan & Ajukan</button>
            <button name="action" value="draft" class="btn btn-ghost w-full" data-testid="surat-keluar-draft-button"><i data-lucide="save" class="w-4 h-4"></i> Simpan sebagai Konsep</button>
        </section>
    </aside>
</form>
<form id="discard-draft-form" method="POST" action="{{ route('surat-keluar.autosave.discard') }}" class="hidden">@csrf @method('DELETE')<input type="hidden" name="key" value="{{ $surat->exists ? $surat->id : 'baru' }}"></form>
@endsection

@push('modals')
@include('partials.confirm-surat-modal')
@endpush
