@extends('layouts.app')
@section('title', 'Tautan Berbagi')
@section('overline', 'Dokumen Saya')
@section('content')
<div class="flex items-center justify-between gap-3 mb-6" data-reveal>
    <p class="text-sm text-slate-500 max-w-lg">Pantau tautan yang pernah Anda bagikan — berapa kali dibuka, kapan berakhir, dan cabut kapan saja.</p>
    <a href="{{ route('dokumen.index') }}" class="btn btn-primary" data-testid="links-new-button"><i data-lucide="plus" class="w-4 h-4"></i><span class="hidden sm:inline">Tautan Baru</span></a>
</div>
@if ($links->isEmpty())
    <x-empty title="Belum ada tautan" icon="link-2">Pilih dokumen di halaman Dokumen Saya lalu tekan “Buat Tautan”.</x-empty>
@else
    <div class="grid md:grid-cols-2 gap-4">
        @foreach ($links as $l)
            @php $active = $l->isActive(); $url = route('share.show', $l->token); @endphp
            <div class="card p-5 {{ $active ? '' : 'opacity-60' }}" data-reveal data-testid="share-link-card-{{ $l->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-bold text-maroon-950 truncate">{{ $l->judul }}</p>
                        <p class="text-xs text-slate-500 mt-1">{{ $l->items_count }} dokumen · dibuat {{ $l->created_at->diffForHumans() }}</p>
                    </div>
                    <x-badge :color="$active ? 'ruby' : 'slate'" data-testid="share-link-status-{{ $l->id }}">{{ $l->revoked_at ? 'Dicabut' : ($active ? 'Aktif' : 'Kedaluwarsa') }}</x-badge>
                </div>
                <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-2xl bg-slate-50 p-2.5"><p class="text-[10px] font-bold uppercase text-slate-400">Akses</p><p class="text-xs font-bold text-slate-700 mt-0.5">{{ $l->permission === 'download' ? 'Lihat+Unduh' : 'Lihat' }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-2.5"><p class="text-[10px] font-bold uppercase text-slate-400">Dibuka</p><p class="text-xs font-bold text-slate-700 mt-0.5">{{ $l->views }}×</p></div>
                    <div class="rounded-2xl bg-slate-50 p-2.5"><p class="text-[10px] font-bold uppercase text-slate-400">Berakhir</p><p class="text-xs font-bold text-slate-700 mt-0.5">{{ $l->expires_at ? $l->expires_at->translatedFormat('d M H:i') : 'Tidak' }}</p></div>
                </div>
                @if ($active)
                    <div class="mt-4 flex gap-2">
                        <input readonly value="{{ $url }}" class="input font-mono text-[11px] !py-2.5">
                        <button type="button" data-copy="{{ $url }}" class="btn btn-soft !px-3.5" data-testid="share-link-copy-{{ $l->id }}"><i data-lucide="copy" class="w-4 h-4"></i></button>
                        <a href="{{ $url }}" target="_blank" class="btn btn-ghost !px-3.5" data-testid="share-link-open-{{ $l->id }}"><i data-lucide="external-link" class="w-4 h-4"></i></a>
                        <form method="POST" action="{{ route('dokumen.revoke', $l) }}" data-confirm="Cabut tautan ini? Penerima tidak bisa membuka lagi.">@csrf @method('DELETE')<button class="btn btn-danger !px-3.5" data-testid="share-link-revoke-{{ $l->id }}"><i data-lucide="ban" class="w-4 h-4"></i></button></form>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif
@endsection
