<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#a3122f">
    <title>Masuk · E-Surat Tabanan</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @foreach (['man-typing', 'man-cover', 'man-thumb'] as $img)<link rel="preload" as="image" href="{{ asset("images/$img.webp") }}">@endforeach
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/login.js'])
    @if ($recaptchaKey)<script src="https://www.google.com/recaptcha/api.js?hl=id" async defer></script>@endif
</head>
<body class="grain min-h-dvh bg-[#fbf6f4] overflow-x-hidden">
<div class="relative min-h-dvh grid lg:grid-cols-[1.15fr_1fr]">
    {{-- 3D Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-[#fde4e8] via-[#fff4f5] to-[#f9dbe1] min-h-[50vh] lg:min-h-dvh" data-tilt-stage data-testid="login-hero">
        <div class="absolute inset-0 opacity-[0.35]" style="background-image: radial-gradient(#d11f43 1px, transparent 1px); background-size: 26px 26px; mask-image: radial-gradient(ellipse at center, #000 30%, transparent 75%);"></div>
        <div data-orbit data-depth="-0.6" class="absolute top-[12%] left-[8%] w-56 h-56 lg:w-96 lg:h-96 rounded-full bg-ruby-400/30 blur-3xl"></div>
        <div data-orbit data-depth="0.8" class="absolute bottom-[6%] right-[6%] w-48 h-48 lg:w-80 lg:h-80 rounded-full bg-gold-400/25 blur-3xl"></div>
        <div data-orbit data-depth="-0.15" class="absolute inset-0 pointer-events-none" aria-hidden="true" data-testid="login-ripple-waves">
            @for ($i = 0; $i < 7; $i++)
                <span class="ripple-ring" style="animation-delay: -{{ $i * 2 }}s"></span>
            @endfor
        </div>

        <div class="absolute top-5 left-5 lg:top-10 lg:left-12 z-20 flex items-center gap-3" data-login-item>
            <img src="{{ asset('images/logo.png') }}" alt="Logo Kabupaten Tabanan" class="w-11 h-11 lg:w-14 lg:h-14 drop-shadow-lg">
            <div>
                <p class="font-display text-lg lg:text-xl font-extrabold text-maroon-950 leading-none">E-Surat</p>
                <p class="text-[10px] lg:text-xs font-bold tracking-[0.2em] text-maroon-700/70 mt-1">KABUPATEN TABANAN</p>
            </div>
        </div>

        <div class="relative z-10 h-full flex items-end lg:items-center justify-center px-4 pt-24 pb-14 lg:p-14 lg:pb-52">
            <div class="relative w-full max-w-[420px] sm:max-w-[520px] lg:max-w-[680px]" data-tilt>
                <div class="relative aspect-[1.3]">
                    <div class="absolute inset-x-[16%] bottom-[-2%] h-[9%] rounded-[50%] bg-maroon-950/25 blur-2xl"></div>
                    <div data-depth="-0.35" class="gate-stage" style="left:-14%;right:-14%;top:-12%;bottom:-8%" data-gate3d data-ornament="{{ asset('images/gate-ornament.jpg') }}" aria-label="Model 3D Gerbang Kabupaten Tabanan" data-testid="login-hero-gate">
                        <canvas data-testid="login-gate-canvas"></canvas>
                        <img src="{{ asset('images/gate.webp') }}" alt="" class="gate-fallback select-none pointer-events-none" draggable="false">
                    </div>
                    <img data-depth="1.3" src="{{ asset('images/envelope.webp') }}" alt="" class="absolute left-[2%] top-[4%] w-[18%] select-none pointer-events-none drop-shadow-xl" draggable="false" data-envelope>
                    <img data-depth="0.9" src="{{ asset('images/plant.webp') }}" alt="" class="absolute right-[-4%] bottom-[1%] w-[17%] z-10 select-none pointer-events-none drop-shadow-xl" draggable="false">

                    <div class="absolute left-[19%] bottom-0 w-[62%]" data-depth="0.25" data-character data-testid="login-character">
                        <img src="{{ asset('images/man-typing.webp') }}" alt="Karakter pegawai sedang mengetik" data-pose="typing" class="relative w-full select-none pointer-events-none drop-shadow-[0_25px_25px_rgba(34,10,15,.3)]" draggable="false" data-testid="login-pose-typing">
                        <img src="{{ asset('images/man-cover.webp') }}" alt="Karakter menutup mata" data-pose="cover" class="absolute inset-0 w-full opacity-0 select-none pointer-events-none drop-shadow-[0_25px_25px_rgba(34,10,15,.3)]" draggable="false" data-testid="login-pose-cover">
                        <img src="{{ asset('images/man-thumb.webp') }}" alt="Karakter mengacungkan jempol" data-pose="thumb" class="absolute inset-0 w-full opacity-0 select-none pointer-events-none drop-shadow-[0_25px_25px_rgba(34,10,15,.3)]" draggable="false" data-testid="login-pose-thumb">
                        <div data-keys class="absolute left-[62%] top-[44%] w-0 h-0 pointer-events-none"></div>
                        <div data-burst class="absolute left-[60%] top-[20%] w-0 h-0 pointer-events-none"></div>
                    </div>

                    <div data-bubble class="absolute right-[0%] top-[2%] max-w-[40%] rounded-3xl rounded-br-md bg-white/95 backdrop-blur px-4 py-2.5 shadow-xl ring-1 ring-ruby-900/5" data-testid="login-speech-bubble">
                        <p class="text-[11px] sm:text-sm font-bold text-maroon-950 leading-snug" data-bubble-text>Om Swastyastu! Silakan masuk.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="hidden lg:block absolute bottom-8 xl:bottom-10 left-1/2 -translate-x-1/2 z-20 w-[min(460px,calc(100%-6rem))]">
            @include('auth.partials.install-app', ['suffix' => '-desktop', 'class' => 'border-white bg-white/90 backdrop-blur shadow-xl shadow-ruby-950/10 ring-1 ring-ruby-900/5'])
        </div>
    </section>

    {{-- Form --}}
    <section class="relative flex items-center justify-center px-4 sm:px-10 py-10 lg:py-16 -mt-10 lg:mt-0 z-20 overflow-x-clip">
        <div class="w-full min-w-0 max-w-md rounded-[2rem] lg:rounded-none bg-white lg:bg-transparent p-5 sm:p-10 lg:p-0 shadow-2xl shadow-ruby-950/10 lg:shadow-none">
            <p class="eyebrow" data-login-item>Sistem Persuratan Elektronik</p>
            <h1 class="font-display mt-3 text-4xl sm:text-5xl font-extrabold tracking-tight text-maroon-950 leading-[1.02]" data-login-item>
                E-Surat <span class="shimmer-text">Tabanan</span>
            </h1>
            <p class="mt-4 text-slate-500 text-sm sm:text-base" data-login-item>Masuk untuk mengelola surat masuk, disposisi, verifikasi, dan tanda tangan elektronik.</p>

            @php $tipe = old('tipe', 'pegawai'); @endphp
            @if ($lockMinutes)
                <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 flex gap-2" data-testid="login-locked-alert"><i data-lucide="lock" class="w-4 h-4 mt-0.5 shrink-0"></i><span>Login dikunci sementara karena terlalu banyak percobaan gagal. Coba lagi dalam {{ $lockMinutes }} menit.</span></div>
            @endif
            @if (session('error'))
                <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" data-testid="login-session-error">{{ session('error') }}</div>
            @endif
            <div class="mt-8 flex gap-1 rounded-full bg-white border border-slate-200 p-1 w-fit" role="tablist" data-login-item data-testid="login-type-tabs">
                <button type="button" class="tab {{ $tipe === 'pegawai' ? 'active' : '' }}" data-login-tab="pegawai" data-testid="login-tab-pegawai"><i data-lucide="id-card" class="w-4 h-4 inline -mt-0.5 mr-1"></i>Pegawai (NIP)</button>
                <button type="button" class="tab {{ $tipe === 'admin' ? 'active' : '' }}" data-login-tab="admin" data-testid="login-tab-admin"><i data-lucide="shield-user" class="w-4 h-4 inline -mt-0.5 mr-1"></i>Admin / Operator</button>
            </div>

            <form id="login-form" method="POST" action="{{ route('login.attempt') }}" class="mt-6 space-y-5" data-testid="login-form">
                @csrf
                <input type="hidden" name="tipe" value="{{ $tipe }}" data-login-tipe data-testid="login-tipe-input">
                <div data-login-item>
                    <label for="username" class="label" data-login-label>{{ $tipe === 'pegawai' ? 'NIP' : 'Username' }}</label>
                    <div class="relative">
                        <i data-lucide="user-round" class="absolute left-4 top-1/2 -translate-y-1/2 w-[18px] h-[18px] text-ruby-700/60"></i>
                        <input id="username" name="username" value="{{ old('username') }}" required autocomplete="username" class="input pl-11" placeholder="{{ $tipe === 'pegawai' ? 'Masukkan NIP' : 'Masukkan username' }}" data-testid="login-username-input">
                    </div>
                    @error('username')<p class="error-text" data-testid="login-error-message">{{ $message }}</p>@enderror
                </div>
                <div data-login-item>
                    <label for="password" class="label">Password</label>
                    <div class="relative">
                        <i data-lucide="lock-keyhole" class="absolute left-4 top-1/2 -translate-y-1/2 w-[18px] h-[18px] text-ruby-700/60"></i>
                        <input id="password" type="password" name="password" required autocomplete="current-password" class="input pl-11 pr-12" placeholder="••••••••" data-testid="login-password-input">
                        <button type="button" data-toggle-password class="absolute right-3 top-1/2 -translate-y-1/2 btn-icon text-slate-400 hover:text-maroon-700" aria-label="Tampilkan password" data-testid="login-toggle-password"><i data-lucide="eye" class="w-[18px] h-[18px]"></i></button>
                    </div>
                </div>
                @if ($recaptchaKey)
                    <div data-login-item class="relative">
                        <div class="relative inline-block origin-left scale-[0.86] min-[400px]:scale-100">
                            <div class="g-recaptcha" data-sitekey="{{ $recaptchaKey }}" data-callback="onCaptchaOk" data-expired-callback="onCaptchaExpired" data-error-callback="onCaptchaExpired" data-testid="login-recaptcha"></div>
                            <span data-thumb-badge class="absolute -right-5 -top-5 grid place-items-center w-14 h-14 rounded-full bg-gradient-to-br from-gold-400 to-gold-600 text-maroon-950 shadow-xl ring-4 ring-white scale-0" data-testid="captcha-thumb-badge"><i data-lucide="thumbs-up" class="w-7 h-7"></i></span>
                        </div>
                        @error('g-recaptcha-response')<p class="error-text" data-testid="login-captcha-error">{{ $message }}</p>@enderror
                    </div>
                @endif
                <div class="flex items-center justify-between" data-login-item>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded accent-ruby-600" data-testid="login-remember-checkbox"> Ingat saya
                    </label>
                </div>
                <button type="submit" class="btn btn-primary w-full py-3.5 text-[15px] group" data-login-item data-testid="login-submit-button">
                    <span data-label>Masuk</span>
                    <i data-lucide="arrow-right" class="w-[18px] h-[18px] transition-transform group-hover:translate-x-1"></i>
                </button>
            </form>
            <div class="lg:hidden pt-10" style="padding-top:2.75rem" data-testid="login-install-app-wrap">
                @include('auth.partials.install-app')
            </div>
            @if ($ssoEnabled)
                <div class="mt-5 flex items-center gap-3 text-xs text-slate-400" data-login-item><span class="h-px flex-1 bg-slate-200"></span>atau<span class="h-px flex-1 bg-slate-200"></span></div>
                <a href="{{ route('sso.login') }}" class="btn btn-soft w-full mt-5 py-3.5" data-login-item data-testid="login-sso-button"><i data-lucide="key-square" class="w-[18px] h-[18px]"></i> Masuk dengan SSO</a>
            @endif
            <div class="mt-8 text-xs text-slate-400 space-y-1" data-login-item>
                <p>© {{ date('Y') }} Kabupaten Tabanan · E-Surat · <a href="{{ route('privacy') }}" class="hover:underline" data-testid="footer-privacy-link">Kebijakan Privasi</a></p>
                <p>Design &amp; Develop by <a href="https://maiharta.com/" target="_blank" rel="noopener" class="font-semibold text-ruby-700 hover:underline" data-testid="footer-maiharta-link">MaiHarta</a></p>
            </div>
        </div>
    </section>
</div>
<script>
document.querySelectorAll('[data-login-tab]').forEach((b) => b.addEventListener('click', () => {
    const t = b.dataset.loginTab, u = document.getElementById('username');
    document.querySelectorAll('[data-login-tab]').forEach((x) => x.classList.toggle('active', x === b));
    document.querySelector('[data-login-tipe]').value = t;
    document.querySelector('[data-login-label]').textContent = t === 'pegawai' ? 'NIP' : 'Username';
    u.placeholder = t === 'pegawai' ? 'Masukkan NIP' : 'Masukkan username';
    u.focus();
}));
</script>
@if ($errors->any())<span data-flash="{{ $errors->first() }}" data-type="error" hidden></span>@endif
</body>
</html>
