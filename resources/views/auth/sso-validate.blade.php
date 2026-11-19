@extends('layouts.public')
@section('title', 'Memverifikasi SSO')
@section('content')
<div class="max-w-md mx-auto mt-16 rounded-[2rem] bg-white p-10 text-center shadow-xl shadow-ruby-950/5" data-testid="sso-validate-page">
    <div class="mx-auto w-12 h-12 rounded-full border-4 border-ruby-100 border-t-maroon-800 animate-spin"></div>
    <h1 class="font-display mt-6 text-2xl font-extrabold text-maroon-950">Memverifikasi akun SSO…</h1>
    <p class="mt-2 text-sm text-slate-500">Mohon tunggu, Anda akan diarahkan otomatis.</p>
    <a href="{{ $verifyUrl }}" class="btn btn-soft mt-6" data-testid="sso-continue-link">Lanjutkan</a>
</div>
<script>setTimeout(() => { location.replace(@json($verifyUrl)); }, 600);</script>
@endsection
