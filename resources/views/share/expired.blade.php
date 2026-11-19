@extends('layouts.public')
@section('title', 'Tautan tidak aktif')
@section('content')
<div class="card max-w-lg mx-auto p-10 text-center mt-10" data-reveal data-testid="share-expired">
    <div class="mx-auto grid place-items-center w-20 h-20 rounded-[1.75rem] bg-rose-50 text-rose-500 animate-floaty"><i data-lucide="link-2-off" class="w-9 h-9"></i></div>
    <h1 class="font-display mt-6 text-2xl font-extrabold text-maroon-950">Tautan sudah tidak aktif</h1>
    <p class="mt-2 text-sm text-slate-500">{{ $link->revoked_at ? 'Pemilik telah mencabut tautan ini.' : 'Masa aktif tautan berakhir pada ' . $link->expires_at->translatedFormat('d M Y H:i') . '.' }} Hubungi pengirim untuk meminta tautan baru.</p>
</div>
@endsection
