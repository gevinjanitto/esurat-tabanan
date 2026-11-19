<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#a3122f">
    <title>@yield('title', 'Dashboard') · E-Surat Tabanan</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="grain min-h-dvh">
    @php $u = auth()->user(); $canCreate = $u->hasRole('operator', 'pimpinan', 'sekpri', 'user'); @endphp
    <script>if (localStorage.getItem('sb-collapsed') === '1') document.documentElement.classList.add('sb-collapsed');</script>

    {{-- Desktop sidebar --}}
    <aside class="sidebar-shell hidden lg:flex fixed top-4 bottom-4 left-4 z-40 w-[17rem] flex-col rounded-[1.75rem] overflow-hidden transition-[width] duration-300" data-testid="desktop-sidebar">
        <div class="pointer-events-none absolute -top-20 -right-16 w-56 h-56 rounded-full bg-ruby-200/40 blur-3xl"></div>
        <div class="pointer-events-none absolute bottom-10 -left-16 w-48 h-48 rounded-full bg-gold-400/15 blur-3xl"></div>
        <div class="relative flex items-center gap-3 px-4 pt-5 pb-4">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 min-w-0 flex-1" data-testid="sidebar-brand-link">
                <span class="relative grid place-items-center shrink-0 w-12 h-12 rounded-2xl bg-white shadow-[0_10px_24px_-10px_rgba(163,18,47,.55)] ring-1 ring-ruby-100">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo Kabupaten Tabanan" class="w-9 h-9">
                    <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                </span>
                <span class="sb-label min-w-0">
                    <span class="block font-display text-[1.35rem] leading-none font-extrabold tracking-tight text-maroon-950">E-Surat</span>
                    <span class="mt-1 inline-flex items-center rounded-full bg-ruby-50 px-2 py-0.5 text-[10px] font-bold tracking-wider text-ruby-700 whitespace-nowrap">KAB. TABANAN</span>
                </span>
            </a>
            <button type="button" class="sb-label grid place-items-center w-8 h-8 rounded-xl text-slate-400 hover:text-maroon-900 hover:bg-ruby-50 transition-colors" data-sb-toggle title="Ciutkan menu" data-testid="sidebar-collapse-button"><i data-lucide="panel-left-close" class="w-4 h-4"></i></button>
        </div>
        <button type="button" class="hidden [html.sb-collapsed_&]:grid mx-auto mb-2 place-items-center w-9 h-9 rounded-xl text-slate-400 hover:text-maroon-900 hover:bg-ruby-50 transition-colors" data-sb-toggle title="Perluas menu" data-testid="sidebar-expand-button"><i data-lucide="panel-left-open" class="w-4 h-4"></i></button>
        @if ($canCreate)
            <div class="relative px-4 pb-4">
                <a href="{{ route('surat-keluar.create') }}" class="group flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-ruby-500 to-maroon-900 px-4 py-3 text-sm font-bold text-white shadow-[0_14px_30px_-14px_rgba(236,47,85,.9)] hover:shadow-[0_18px_34px_-12px_rgba(236,47,85,.9)] transition-shadow" title="Buat Surat" data-testid="sidebar-create-surat-button">
                    <i data-lucide="pen-line" class="w-4 h-4 transition-transform group-hover:-rotate-12"></i><span class="sb-label">Buat Surat</span>
                </a>
            </div>
        @endif
        <div class="relative flex-1 overflow-y-auto scrollbar-thin px-3 pb-4">
            @include('partials.nav', ['prefix' => 'sidebar'])
        </div>
        <div class="relative m-3 rounded-2xl bg-gradient-to-br from-maroon-950 to-[#4a0c1c] p-3 flex items-center gap-3 text-white">
            <span class="grid place-items-center shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br from-gold-400 to-gold-600 text-maroon-950 font-bold text-sm">{{ $u->initials }}</span>
            <div class="sb-label min-w-0 flex-1">
                <p class="text-sm font-bold truncate">{{ $u->name }}</p>
                <p class="text-[11px] text-white/55 truncate">{{ $u->role_label }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="sb-label">@csrf
                <button class="btn-icon text-white/60 hover:text-white hover:bg-white/10 rounded-xl transition-colors" title="Keluar" data-testid="sidebar-logout-button"><i data-lucide="log-out" class="w-4 h-4"></i></button>
            </form>
        </div>
    </aside>

    {{-- Mobile drawer --}}
    <div id="mobile-drawer" class="hidden lg:hidden fixed inset-0 z-[80]">
        <div class="drawer-bg absolute inset-0 bg-maroon-950/40 backdrop-blur-sm" data-drawer-close></div>
        <aside class="sidebar-shell absolute top-3 bottom-3 left-3 w-[82%] max-w-xs rounded-[1.75rem] p-4 overflow-y-auto">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <span class="grid place-items-center w-11 h-11 rounded-2xl bg-white ring-1 ring-ruby-100 shadow"><img src="{{ asset('images/logo.png') }}" alt="" class="w-8 h-8"></span>
                    <span class="font-display text-lg font-extrabold text-maroon-950">E-Surat</span>
                </div>
                <button class="btn-icon text-slate-500 hover:bg-ruby-50" data-drawer-close data-testid="mobile-drawer-close"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            @if ($canCreate)
                <a href="{{ route('surat-keluar.create') }}" class="mb-5 flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-ruby-500 to-maroon-900 px-4 py-3 text-sm font-bold text-white" data-testid="drawer-create-surat-button"><i data-lucide="pen-line" class="w-4 h-4"></i> Buat Surat</a>
            @endif
            @include('partials.nav', ['prefix' => 'drawer'])
            <form method="POST" action="{{ route('logout') }}" class="mt-6">@csrf
                <button class="nav-link w-full !text-rose-700" data-testid="drawer-logout-button"><span class="nav-ico !bg-rose-50 !text-rose-600"><i data-lucide="log-out" class="w-[18px] h-[18px]"></i></span> Keluar</button>
            </form>
        </aside>
    </div>

    <div class="main-shell lg:pl-[18.5rem] min-h-dvh flex flex-col transition-[padding] duration-300">
        {{-- Topbar --}}
        <header class="sticky top-0 z-30 px-3 sm:px-6 lg:px-6 pt-3 lg:pt-4" data-testid="topbar">
            <div class="flex items-center gap-3 px-3 sm:px-5 h-16 lg:h-[4.5rem] rounded-[1.5rem] bg-white/70 backdrop-blur-2xl border border-white shadow-[0_20px_40px_-28px_rgba(34,10,15,.35)]">
                <button class="lg:hidden btn-icon bg-white border border-slate-200" data-drawer-open data-testid="mobile-menu-button" aria-label="Menu"><i data-lucide="menu" class="w-5 h-5"></i></button>
                <a href="{{ route('dashboard') }}" class="lg:hidden flex items-center gap-2"><img src="{{ asset('images/logo.png') }}" class="w-8 h-8" alt=""><span class="font-display font-extrabold text-maroon-900">E-Surat</span></a>
                <div class="hidden lg:block min-w-0">
                    <p class="eyebrow">@yield('overline', 'E-Surat Tabanan')</p>
                    <h1 class="font-display text-[1.6rem] leading-tight font-bold tracking-tight text-maroon-950 truncate">@yield('title', 'Dashboard')</h1>
                </div>
                <div class="ml-auto flex items-center gap-2">
                    <span class="hidden xl:inline-flex items-center gap-2 rounded-full bg-ruby-50/80 px-3.5 py-2 text-xs font-semibold text-maroon-900" data-testid="topbar-date"><i data-lucide="calendar-days" class="w-4 h-4 text-ruby-600"></i>{{ now()->translatedFormat('l, d F Y') }}</span>
                    @if ($canCreate)
                        <a href="{{ route('surat-keluar.create') }}" class="hidden md:inline-flex lg:hidden items-center gap-2 rounded-full bg-maroon-950 px-4 py-2 text-xs font-bold text-white" data-testid="topbar-create-surat-button"><i data-lucide="pen-line" class="w-4 h-4"></i> Buat Surat</a>
                    @endif
                    @unless ($u->hasRole('admin', 'operator'))
                    <div class="relative">
                        <button class="relative btn-icon bg-white border border-slate-200 hover:border-ruby-300 transition-colors" data-dropdown-toggle="notif-dd" data-testid="notifications-button" aria-label="Notifikasi">
                            <i data-lucide="bell" class="w-5 h-5 text-maroon-800"></i>
                            @if ($unreadCount)
                                <span class="absolute -top-1 -right-1 min-w-5 h-5 px-1 grid place-items-center rounded-full bg-gold-500 text-maroon-950 text-[10px] font-extrabold" data-testid="notifications-unread-count">{{ $unreadCount }}</span>
                            @endif
                        </button>
                        <div id="notif-dd" data-dropdown class="hidden absolute right-0 mt-3 w-[min(22rem,calc(100vw-2rem))] card p-2 z-50" data-testid="notifications-dropdown">
                            <div class="flex items-center justify-between px-3 py-2">
                                <p class="font-bold text-maroon-950">Notifikasi</p>
                                <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="text-xs font-semibold text-ruby-700 hover:underline" data-testid="notifications-read-all">Tandai dibaca</button></form>
                            </div>
                            <div class="max-h-80 overflow-y-auto scrollbar-thin">
                                @forelse ($notifications as $n)
                                    <a href="{{ route('notifications.open', $n) }}" class="flex gap-3 rounded-2xl px-3 py-2.5 hover:bg-ruby-50/70 transition-colors" data-testid="notification-item-{{ $n->id }}">
                                        <span class="mt-1.5 w-2 h-2 shrink-0 rounded-full {{ $n->read_at ? 'bg-slate-200' : 'bg-ruby-500' }}"></span>
                                        <span class="min-w-0"><span class="block text-sm font-semibold text-slate-800">{{ $n->judul }}</span><span class="block text-xs text-slate-500 truncate">{{ $n->pesan }}</span><span class="block text-[10px] text-slate-400 mt-0.5">{{ $n->created_at->diffForHumans() }}</span></span>
                                    </a>
                                @empty
                                    <p class="px-3 py-8 text-center text-sm text-slate-400">Belum ada notifikasi.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    @endunless
                    <div class="relative">
                        <button type="button" class="flex items-center gap-3 rounded-full bg-white border border-slate-200 pl-1.5 pr-3 sm:pr-4 py-1.5 hover:border-ruby-300 transition-colors" data-dropdown-toggle="account-dd" data-testid="topbar-account-button">
                            <span class="grid place-items-center w-8 h-8 rounded-full bg-maroon-900 text-white text-xs font-bold">{{ $u->initials }}</span>
                            <span class="hidden sm:block text-sm font-semibold text-slate-700 max-w-32 truncate">{{ \Illuminate\Support\Str::words($u->name, 2, '') }}</span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400"></i>
                        </button>
                        <div id="account-dd" data-dropdown class="hidden absolute right-0 mt-3 w-60 card p-2 z-50" data-testid="account-dropdown">
                            <div class="px-3 py-2 border-b border-slate-100 mb-1"><p class="text-sm font-bold text-maroon-950 truncate">{{ $u->name }}</p><p class="text-[11px] text-slate-500">{{ $u->role_label }}{{ $u->jabatan ? ' · ' . $u->jabatan->nama : '' }}</p></div>
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 rounded-2xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-ruby-50" data-testid="topbar-profile-link"><i data-lucide="user-round-cog" class="w-4 h-4"></i> Profil</a>
                            <a href="{{ route('profile.edit') }}#password" class="flex items-center gap-2 rounded-2xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-ruby-50" data-testid="topbar-password-link"><i data-lucide="key-round" class="w-4 h-4"></i> Ubah Password</a>
                            <form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full flex items-center gap-2 rounded-2xl px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50" data-testid="topbar-logout-button"><i data-lucide="log-out" class="w-4 h-4"></i> Keluar</button></form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main id="page-main" class="flex-1 px-4 sm:px-8 pt-5 lg:pt-8 pb-32 lg:pb-12">
            <div class="lg:hidden mb-5" data-reveal>
                <p class="eyebrow">@yield('overline', 'E-Surat Tabanan')</p>
                <h1 class="font-display text-[1.7rem] leading-tight font-bold tracking-tight text-maroon-950">@yield('title', 'Dashboard')</h1>
            </div>
            @yield('content')
        </main>
    </div>

    {{-- Mobile bottom nav --}}
    <nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 px-3 safe-bottom" data-testid="mobile-bottom-nav">
        <div class="relative mx-auto max-w-md grid grid-cols-5 items-end rounded-[1.75rem] bg-[#a3122f]/95 backdrop-blur-xl px-2 py-2 shadow-[0_20px_40px_-12px_rgba(34,10,15,.6)]">
            @php
                $bn = [
                    ['Beranda', 'house', 'dashboard', 'dashboard'],
                    match (true) {
                        $u->hasRole('admin') => ['Aktivitas', 'activity', 'logs.index', 'logs.*'],
                        $u->hasRole('operator') => ['Masuk', 'inbox', 'surat-masuk.index', 'surat-masuk.*'],
                        default => ['Disposisi', 'git-pull-request-arrow', 'disposisi.inbox', 'disposisi.*'],
                    },
                    null,
                    ['Dokumen', 'folder-heart', 'dokumen.index', 'dokumen.*'],
                    ['Profil', 'circle-user-round', 'profile.edit', 'profile.*'],
                ];
            @endphp
            @foreach ($bn as $item)
                @if ($item === null)
                    <a href="{{ $u->hasRole('admin') ? route('users.index') : route('surat-keluar.index') }}" class="-mt-8 mx-auto grid place-items-center w-14 h-14 rounded-2xl bg-gradient-to-br from-ruby-400 to-maroon-700 text-white shadow-lg shadow-ruby-900/40 ring-4 ring-[#f6f3f1] active:scale-95 transition-transform" data-testid="mobile-bottom-nav-surat-keluar" aria-label="Surat Keluar">
                        <i data-lucide="send" class="w-6 h-6"></i>
                    </a>
                @else
                    @php $on = request()->routeIs($item[3]); @endphp
                    <a href="{{ route($item[2]) }}" class="flex flex-col items-center gap-1 py-1.5 rounded-2xl text-[10px] font-semibold transition-colors {{ $on ? 'text-white' : 'text-ruby-100/50' }}" data-testid="mobile-bottom-nav-{{ \Illuminate\Support\Str::slug($item[0]) }}">
                        <span class="grid place-items-center w-10 h-7 rounded-full transition-colors {{ $on ? 'bg-ruby-500/25 text-ruby-300' : '' }}"><i data-lucide="{{ $item[1] }}" class="w-5 h-5"></i></span>
                        {{ $item[0] }}
                    </a>
                @endif
            @endforeach
        </div>
    </nav>

    @foreach (['success', 'error'] as $type)
        @if (session($type))<span data-flash="{{ session($type) }}" data-type="{{ $type }}" hidden></span>@endif
    @endforeach
    @if ($errors->any())<span data-flash="{{ $errors->first() }}" data-type="error" hidden></span>@endif
    @stack('modals')
    @stack('scripts')
</body>
</html>
