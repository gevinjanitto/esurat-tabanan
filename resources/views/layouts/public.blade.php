<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') · E-Surat Tabanan</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="grain min-h-dvh bg-[#fbf7f5]">
    <div class="absolute inset-x-0 top-0 h-80 bg-gradient-to-b from-[#fbe3e7] to-transparent pointer-events-none"></div>
    <header class="relative max-w-5xl mx-auto px-5 pt-6 flex items-center gap-3">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-11 h-11 drop-shadow">
        <div><p class="font-display text-lg font-extrabold text-maroon-950 leading-none">E-Surat</p><p class="text-[10px] font-bold tracking-[0.2em] text-maroon-700/70 mt-1">KABUPATEN TABANAN</p></div>
    </header>
    <main class="relative max-w-5xl mx-auto px-4 sm:px-5 py-8 pb-16">@yield('content')</main>
</body>
</html>
