@extends('layouts.public')
@section('title', 'Kebijakan Privasi')
@section('content')
<article class="rounded-[2rem] bg-white p-6 sm:p-10 shadow-xl shadow-ruby-950/5 ring-1 ring-ruby-900/5 max-w-3xl" data-testid="privacy-page">
    <p class="eyebrow">E-Surat Tabanan</p>
    <h1 class="font-display mt-2 text-4xl sm:text-5xl font-extrabold tracking-tight text-maroon-950">Kebijakan Privasi</h1>
    <p class="mt-3 text-sm text-slate-500">Berlaku untuk aplikasi web dan aplikasi mobile E-Surat.</p>
    <div class="mt-8 space-y-6 text-[15px] leading-relaxed text-slate-700">
        <section><h2 class="font-display text-lg font-bold text-maroon-900">1. Data yang kami kumpulkan</h2><p class="mt-1">Nama, NIP/username, satuan kerja, jabatan, alamat email kantor, isi dan berkas surat dinas, catatan disposisi, serta catatan aktivitas (waktu, alamat IP, jenis perangkat). Aplikasi mobile juga menyimpan token notifikasi perangkat.</p></section>
        <section><h2 class="font-display text-lg font-bold text-maroon-900">2. Tujuan penggunaan</h2><p class="mt-1">Data dipakai hanya untuk proses persuratan internal Kabupaten Tabanan: pencatatan surat, disposisi, verifikasi, tanda tangan elektronik, notifikasi, dan audit keamanan.</p></section>
        <section><h2 class="font-display text-lg font-bold text-maroon-900">3. Penyimpanan &amp; keamanan</h2><p class="mt-1">Data disimpan di server milik bank. Akses dibatasi sesuai peran pengguna, login dilindungi captcha, penguncian akun setelah gagal berulang, dan pembatasan jumlah permintaan.</p></section>
        <section><h2 class="font-display text-lg font-bold text-maroon-900">4. Berbagi data</h2><p class="mt-1">Data tidak dijual atau dibagikan ke pihak luar. Notifikasi push dikirim melalui Firebase Cloud Messaging hanya berisi judul singkat surat.</p></section>
        <section><h2 class="font-display text-lg font-bold text-maroon-900">5. Hak pengguna</h2><p class="mt-1">Pengguna dapat meminta perbaikan data pribadi melalui administrator sistem atau Divisi Teknologi Informasi.</p></section>
    </div>
    <a href="{{ route('login') }}" class="btn btn-primary mt-10" data-testid="privacy-back-button"><i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali</a>
</article>
@endsection
