@extends('errors.layout')
@section('code', '503')
@section('heading', 'Sistem sedang disiapkan')
@section('message', 'Aplikasi E-Surat belum siap digunakan. Administrator perlu menyelesaikan penyiapan database terlebih dahulu.')
@section('extra')
    <div class="hint" data-testid="setup-hint">{{ $hint }}</div>
@endsection
