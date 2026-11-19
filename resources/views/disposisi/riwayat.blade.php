@extends('layouts.app')
@section('title', 'Pohon Disposisi')
@section('overline', $suratMasuk->nomor_agenda)
@section('content')
<section class="card p-6 mb-6" data-reveal>
    <div class="flex flex-wrap items-start gap-4">
        <div class="min-w-0 flex-1">
            <p class="eyebrow">Riwayat disposisi</p>
            <a href="{{ route('surat-masuk.show', $suratMasuk) }}" class="mt-1 block font-display text-xl sm:text-2xl font-bold text-maroon-950 hover:text-maroon-700" data-testid="riwayat-surat-perihal">{{ $suratMasuk->perihal }}</a>
            <p class="text-sm text-slate-500">{{ $suratMasuk->pengirim }} · {{ $suratMasuk->nomor_surat }}</p>
        </div>
        <form method="GET" class="flex flex-wrap gap-2" data-testid="riwayat-filter-form">
            <select name="root" class="input !py-2 rounded-full w-56" onchange="this.form.submit()" data-testid="riwayat-root-filter">
                <option value="">Semua akar ({{ $allRoots->count() }})</option>
                @foreach ($allRoots as $r)<option value="{{ $r->id }}" @selected($rootId === $r->id)>{{ $r->dari->name }} → {{ $r->ke->name }}</option>@endforeach
            </select>
            <select name="satker" class="input !py-2 rounded-full w-56" onchange="this.form.submit()" data-testid="riwayat-satker-filter">
                <option value="">Semua satker</option>
                @foreach ($satkers as $s)<option value="{{ $s->id }}" @selected($satkerId === $s->id)>{{ $s->nama }}</option>@endforeach
            </select>
        </form>
    </div>
    @if ($suratMasuk->teruskans->where('status', 'aktif')->isNotEmpty())
        <div class="mt-4 flex flex-wrap items-center gap-1.5 text-xs text-slate-500"><i data-lucide="forward" class="w-3.5 h-3.5"></i> Diteruskan ke:
            @foreach ($suratMasuk->teruskans->where('status', 'aktif') as $t)<span class="badge badge-slate">{{ $t->target_label }}</span>@endforeach
        </div>
    @endif
</section>

<section class="card p-4 sm:p-8 overflow-x-auto scrollbar-thin" data-reveal data-testid="disposisi-tree">
    @php $visibleRoots = $roots->filter(fn ($r) => isset($keep[$r->id])); @endphp
    @if ($visibleRoots->isEmpty())
        <p class="text-center text-slate-400 py-10">Tidak ada disposisi untuk filter ini.</p>
    @else
        <ul class="tree min-w-[560px] space-y-4">
            @foreach ($visibleRoots as $node)
                @include('disposisi._node', ['node' => $node, 'depth' => 0])
            @endforeach
        </ul>
    @endif
</section>
@endsection
