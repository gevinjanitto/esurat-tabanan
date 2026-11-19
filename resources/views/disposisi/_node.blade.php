@php
    $children = collect($byParent->get($node->id, []))->filter(fn ($c) => isset($keep[$c->id]));
    $c = ['terkirim' => 'amber', 'dibaca' => 'sky', 'ditindaklanjuti' => 'ruby'][$node->status] ?? 'slate';
@endphp
<li class="tree-node relative {{ $depth ? 'pl-8' : '' }}" data-testid="tree-node-{{ $node->id }}">
    <div class="relative inline-flex min-w-[320px] max-w-xl items-start gap-3 rounded-2xl border {{ $depth ? 'border-slate-200 bg-white' : 'border-ruby-200 bg-ruby-50/50' }} p-3.5">
        <span class="grid place-items-center w-10 h-10 shrink-0 rounded-2xl bg-maroon-900 text-white text-xs font-bold">{{ $node->ke->initials }}</span>
        <div class="min-w-0">
            <p class="text-sm font-bold text-slate-800">{{ $node->ke->name }} <span class="font-normal text-slate-400">· {{ $node->ke->jabatan?->nama ?? $node->ke->role_label }}</span></p>
            <p class="text-[11px] text-slate-500">dari {{ $node->dari->name }} · {{ $node->ke->satker?->nama ?? '—' }} · {{ $node->created_at->translatedFormat('d M Y H:i') }}</p>
            <p class="text-sm text-slate-700 mt-1">{{ $node->instruksi }}@if ($node->catatan) <span class="text-slate-500">— {{ $node->catatan }}</span>@endif</p>
            <div class="mt-1.5 flex flex-wrap items-center gap-2"><x-badge :color="$c">{{ $node->status_label }}</x-badge>@if ($node->batas_waktu)<span class="text-[11px] text-slate-400">batas {{ $node->batas_waktu->translatedFormat('d M Y') }}</span>@endif</div>
            @if ($node->tindak_lanjut)<p class="mt-2 text-xs rounded-xl bg-white border border-ruby-100 text-ruby-900 px-3 py-1.5">{{ $node->tindak_lanjut }}</p>@endif
        </div>
    </div>
    @if ($children->isNotEmpty())
        <ul class="tree-children mt-3 space-y-3 border-l-2 border-dashed border-ruby-200 ml-5">
            @foreach ($children as $child)
                @include('disposisi._node', ['node' => $child, 'depth' => $depth + 1])
            @endforeach
        </ul>
    @endif
</li>
