@php
    $chosen = collect($selected)->map(fn ($v) => (int) $v);
    $groups = $grupOpds->map(fn ($g) => [$g->nama, $g->satkers])->push(['Tanpa grup', $satkers->whereNull('grup_opd_id')])->filter(fn ($g) => $g[1]->isNotEmpty())->values();
@endphp
<div class="rounded-2xl border border-slate-200 p-3" data-opd-picker data-testid="opd-picker-{{ $jenis }}">
    <label class="label">{{ $label }}</label>
    <ul class="flex flex-wrap gap-1.5 mb-2 empty:hidden" data-opd-chips data-confirm-list="{{ $label }}" data-testid="opd-chips-{{ $jenis }}"></ul>
    <div class="relative mb-2"><i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i><input type="search" class="input !py-2 pl-10 text-sm" placeholder="Cari OPD atau grup…" autocomplete="off" data-opd-search data-testid="opd-search-{{ $jenis }}"></div>
    <div class="max-h-60 overflow-y-auto scrollbar-thin space-y-3 pr-1">
        @foreach ($groups as $gi => [$gname, $items])
            <div data-opd-group>
                <label class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider text-maroon-800 cursor-pointer"><input type="checkbox" class="w-4 h-4 accent-ruby-600" data-opd-group-toggle data-testid="opd-group-{{ $jenis }}-{{ $gi }}"> {{ $gname }} <span class="font-mono font-normal text-slate-400">({{ $items->count() }})</span></label>
                <div class="mt-1.5 grid sm:grid-cols-2 gap-1">
                    @foreach ($items as $o)
                        <label class="flex items-center gap-2 rounded-xl px-2.5 py-1.5 text-sm text-slate-700 hover:bg-ruby-50 cursor-pointer" data-opd-item data-search="{{ \Illuminate\Support\Str::lower($o->nama . ' ' . $o->kode . ' ' . $gname) }}"><input type="checkbox" name="{{ $jenis }}_satker[]" value="{{ $o->id }}" class="w-4 h-4 accent-ruby-600" @checked($chosen->contains($o->id)) data-testid="opd-{{ $jenis }}-{{ $o->id }}"><span data-opd-name>{{ $o->nama }}</span></label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
