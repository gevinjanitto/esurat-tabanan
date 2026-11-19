@extends('layouts.app')
@section('title', 'Template Surat')
@section('overline', 'Setting Surat')
@section('content')
<div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6" data-reveal>
    <form class="relative flex-1 max-w-md" method="GET"><i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i><input name="q" value="{{ request('q') }}" class="input pl-11 rounded-full" placeholder="Cari template…" data-testid="template-surat-search-input"></form>
    <a href="{{ route('template-surat.create') }}" class="btn btn-primary sm:ml-auto" data-testid="template-surat-add-button"><i data-lucide="plus" class="w-4 h-4"></i> Tambah Template</a>
</div>
@if ($templates->isEmpty())
    <x-empty title="Belum ada template surat" icon="file-type-2">Buat template berisi kop dan isi surat dengan kode isian, lalu pegawai cukup mengisi formulir untuk menghasilkan PDF.</x-empty>
@else
<div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-5" data-reveal>
    @foreach ($templates as $t)
        <article class="card card-hover p-6 flex flex-col" data-testid="template-surat-card-{{ $t->id }}">
            <div class="flex items-start justify-between gap-3">
                <span class="grid place-items-center w-11 h-11 rounded-2xl bg-blush-100 text-maroon-700"><i data-lucide="file-type-2" class="w-5 h-5"></i></span>
                <span class="badge {{ $t->status ? 'badge-ruby' : 'badge-slate' }}">{{ $t->status ? 'Aktif' : 'Nonaktif' }}</span>
            </div>
            <h3 class="mt-4 font-display text-lg font-bold text-maroon-950">{{ $t->nama }}</h3>
            <p class="text-sm text-slate-500 mt-1 line-clamp-2 flex-1">{{ $t->deskripsi ?: 'Tanpa deskripsi' }}</p>
            <div class="mt-4 flex flex-wrap gap-1.5 text-[11px]"><span class="badge badge-slate">{{ $t->isians_count }} isian</span><span class="badge badge-gold">{{ \App\Models\TemplateSurat::COUNTER[$t->counter_penomoran] ?? $t->counter_penomoran }}</span></div>
            <div class="mt-5 flex gap-2">
                <a href="{{ route('template-surat.edit', $t) }}" class="btn btn-soft flex-1" data-testid="template-surat-edit-{{ $t->id }}"><i data-lucide="pencil" class="w-4 h-4"></i> Ubah</a>
                <form method="POST" action="{{ route('template-surat.destroy', $t) }}" data-confirm="Hapus template ini?">@csrf @method('DELETE')<button class="btn btn-danger" data-testid="template-surat-delete-{{ $t->id }}"><i data-lucide="trash-2" class="w-4 h-4"></i></button></form>
            </div>
        </article>
    @endforeach
</div>
<div class="mt-6">{{ $templates->links() }}</div>
@endif
@endsection
