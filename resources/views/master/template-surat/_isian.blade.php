<div class="rounded-2xl border border-slate-200 p-3 space-y-2" data-repeater-row>
    <div class="flex items-center gap-2">
        <span class="grid place-items-center w-6 h-6 rounded-full bg-maroon-900 text-white text-[10px] font-bold" data-row-no>1</span>
        <input data-name="isian[__i__][nama]" value="{{ $row['nama'] }}" class="input !py-2 text-sm flex-1" placeholder="Nama field" required data-testid="isian-nama-input">
        <button type="button" class="btn-icon !p-1.5 text-slate-400 hover:text-maroon-700" data-repeater-act="up" aria-label="Naik"><i data-lucide="chevron-up" class="w-4 h-4"></i></button>
        <button type="button" class="btn-icon !p-1.5 text-slate-400 hover:text-rose-600" data-repeater-act="del" aria-label="Hapus" data-testid="isian-remove-button"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>
    <div class="grid grid-cols-[1fr_120px] gap-2">
        <input data-name="isian[__i__][kode]" value="{{ $row['kode'] }}" class="input !py-2 font-mono text-xs uppercase" placeholder="KODE_ISIAN" pattern="[A-Za-z0-9_]+" required data-testid="isian-kode-input">
        <select data-name="isian[__i__][jenis]" class="input !py-2 text-xs" data-testid="isian-jenis-select">@foreach (\App\Models\TemplateSurat::JENIS_ISIAN as $k => $v)<option value="{{ $k }}" @selected(($row['jenis'] ?? 'teks') === $k)>{{ $v }}</option>@endforeach</select>
    </div>
    <button type="button" class="text-[11px] font-semibold text-ruby-700 hover:underline" onclick="const k=this.closest('[data-repeater-row]').querySelector('[data-name$=&quot;[kode]&quot;]').value.trim().toUpperCase(); if(k){this.dataset.insertCode=k;}">Sisipkan kode ke editor</button>
</div>
