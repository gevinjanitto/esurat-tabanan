@extends('layouts.app')
@section('title', 'Template Nomor Surat')
@section('overline', 'Setting Surat')
@section('content')
<div class="flex items-center gap-3 mb-6" data-reveal>
    <p class="text-sm text-slate-500 max-w-xl">Format nomor surat keluar per template surat dan/atau satker. Nomor urut otomatis dihitung per satker per tahun, mulai dari Nomor Inisiasi.</p>
    <a href="{{ route('template-nomor.create') }}" class="btn btn-primary ml-auto" data-testid="template-nomor-add-button"><i data-lucide="plus" class="w-4 h-4"></i> Tambah</a>
</div>
<div class="card overflow-hidden" data-reveal>
    <div class="overflow-x-auto">
        <table class="table-modern" data-testid="template-nomor-table">
            <thead><tr><th>Nama</th><th>Contoh format</th><th>Template surat</th><th>Satker</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
                @forelse ($rows as $r)
                    <tr data-testid="template-nomor-row-{{ $r->id }}">
                        <td class="font-semibold text-maroon-950">{{ $r->nama }}</td>
                        <td><span class="font-mono text-sm text-ruby-800">{{ $r->sample }}</span></td>
                        <td class="text-slate-600">{{ $r->templateSurat?->nama ?? 'Semua template' }}</td>
                        <td class="text-slate-600">{{ $r->satker?->nama ?? 'Semua satker' }}</td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('template-nomor.edit', $r) }}" class="btn-icon inline-grid text-slate-500 hover:bg-ruby-50 hover:text-maroon-700" data-testid="template-nomor-edit-{{ $r->id }}"><i data-lucide="pencil" class="w-4 h-4"></i></a>
                            <form method="POST" action="{{ route('template-nomor.destroy', $r) }}" class="inline" data-confirm="Hapus template nomor ini?">@csrf @method('DELETE')<button class="btn-icon text-slate-500 hover:bg-rose-50 hover:text-rose-600" data-testid="template-nomor-delete-{{ $r->id }}"><i data-lucide="trash-2" class="w-4 h-4"></i></button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-slate-400 py-10">Belum ada template nomor.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">{{ $rows->links() }}</div>
@endsection
