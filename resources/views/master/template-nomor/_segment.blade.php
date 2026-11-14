<div class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white p-2" data-repeater-row>
    <span class="grid place-items-center w-6 h-6 shrink-0 rounded-full bg-maroon-900 text-white text-[10px] font-bold" data-row-no>1</span>
    <select data-name="segments[__i__][type]" class="input !py-2 text-sm flex-1" data-testid="segment-type-select">@foreach (\App\Models\TemplateNomor::SEGMENTS as $k => [$l, $ex])<option value="{{ $k }}" @selected(($seg['type'] ?? '') === $k)>{{ $l }}</option>@endforeach</select>
    <input type="text" data-name="segments[__i__][value]" value="{{ $seg['value'] ?? '' }}" maxlength="40" class="input !py-2 text-sm w-36 font-mono" placeholder="Teks" data-testid="segment-value-input">
    <button type="button" class="btn-icon !p-1.5 text-slate-400 hover:text-maroon-700" data-repeater-act="up" aria-label="Naik"><i data-lucide="chevron-up" class="w-4 h-4"></i></button>
    <button type="button" class="btn-icon !p-1.5 text-slate-400 hover:text-rose-600" data-repeater-act="del" aria-label="Hapus" data-testid="segment-remove-button"><i data-lucide="x" class="w-4 h-4"></i></button>
</div>
