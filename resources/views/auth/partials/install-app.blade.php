@php
    $androidUrl = config('services.mobile.android_url');
    $iosUrl = config('services.mobile.ios_url');
    // PWA aplikasi mobile: dipasang langsung dari browser HP, tanpa APK.
    $pwaUrl = config('services.mobile.pwa_url') ?: (is_file(public_path('m/index.html')) ? url('/m') : null);
    $pwaUrl = $pwaUrl ? rtrim($pwaUrl, '/') . '/' : null;
    // PWA di server ini (/m): halaman login sendiri menawarkan instal sehingga tombol Android langsung memasang.
    $pwaLocal = ! config('services.mobile.pwa_url') && $pwaUrl && is_file(public_path('esurat-sw.js'));
@endphp
<div class="rounded-2xl border p-4 sm:p-5 {{ $class ?? 'border-slate-200 bg-white shadow-sm' }}" data-testid="login-install-app{{ $suffix ?? '' }}">
    <div class="flex items-center gap-3">
        <span class="grid place-items-center w-10 h-10 shrink-0 rounded-xl bg-ruby-50 text-ruby-700"><i data-lucide="bell-ring" class="w-5 h-5"></i></span>
        <div class="min-w-0">
            <p class="text-sm font-bold text-maroon-950">Instal aplikasi E-Surat</p>
            <p class="text-xs text-slate-500">Notifikasi surat &amp; disposisi langsung ke HP</p>
        </div>
    </div>
    <div class="mt-4 grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)] gap-2.5">
        @if ($pwaUrl)
            <a href="{{ $pwaUrl }}?install=android" data-pwa-install class="flex items-center gap-2.5 rounded-xl bg-maroon-950 text-white min-w-0 px-3 py-3 hover:bg-maroon-900 transition" data-testid="login-install-android{{ $suffix ?? '' }}">
                <i data-lucide="smartphone" class="w-5 h-5 shrink-0"></i>
                <span class="min-w-0 leading-tight text-left"><span class="block text-[10px] opacity-75" data-pwa-label>Pasang aplikasi</span><span class="block text-sm font-bold">Android</span></span>
            </a>
        @elseif ($androidUrl)
            <a href="{{ $androidUrl }}" download="E-Surat-Tabanan.apk" type="application/vnd.android.package-archive" class="flex items-center gap-2.5 rounded-xl bg-maroon-950 text-white min-w-0 px-3 py-3 hover:bg-maroon-900 transition" data-testid="login-install-android{{ $suffix ?? '' }}">
                <i data-lucide="smartphone" class="w-5 h-5 shrink-0"></i>
                <span class="min-w-0 leading-tight text-left"><span class="block text-[10px] opacity-75">Unduh APK</span><span class="block text-sm font-bold">Android</span></span>
            </a>
        @else
            <span aria-disabled="true" class="flex items-center gap-2.5 rounded-xl bg-slate-100 text-slate-400 min-w-0 px-3 py-3 cursor-not-allowed" data-testid="login-install-android{{ $suffix ?? '' }}">
                <i data-lucide="smartphone" class="w-5 h-5 shrink-0"></i>
                <span class="min-w-0 leading-tight text-left"><span class="block text-[10px]">Segera hadir</span><span class="block text-sm font-bold">Android</span></span>
            </span>
        @endif
        @if ($pwaUrl)
            <a href="{{ $pwaUrl }}?install=ios" class="flex items-center gap-2.5 rounded-xl bg-maroon-950 text-white min-w-0 px-3 py-3 hover:bg-maroon-900 transition" data-testid="login-install-ios{{ $suffix ?? '' }}">
                <i data-lucide="tablet-smartphone" class="w-5 h-5 shrink-0"></i>
                <span class="min-w-0 leading-tight text-left"><span class="block text-[10px] opacity-75">Pasang aplikasi</span><span class="block text-sm font-bold">iPhone</span></span>
            </a>
        @elseif ($iosUrl)
            <a href="{{ $iosUrl }}" target="_blank" rel="noopener" class="flex items-center gap-2.5 rounded-xl bg-maroon-950 text-white min-w-0 px-3 py-3 hover:bg-maroon-900 transition" data-testid="login-install-ios{{ $suffix ?? '' }}">
                <i data-lucide="tablet-smartphone" class="w-5 h-5 shrink-0"></i>
                <span class="min-w-0 leading-tight text-left"><span class="block text-[10px] opacity-75">Buka TestFlight</span><span class="block text-sm font-bold">iPhone</span></span>
            </a>
        @else
            <span aria-disabled="true" class="flex items-center gap-2.5 rounded-xl bg-slate-100 text-slate-400 min-w-0 px-3 py-3 cursor-not-allowed" data-testid="login-install-ios{{ $suffix ?? '' }}">
                <i data-lucide="tablet-smartphone" class="w-5 h-5 shrink-0"></i>
                <span class="min-w-0 leading-tight text-left"><span class="block text-[10px]">Segera hadir</span><span class="block text-sm font-bold">iPhone</span></span>
            </span>
        @endif
    </div>
    @if ($pwaUrl)
        <p class="mt-3 text-[11px] leading-relaxed text-slate-400" data-testid="login-install-pwa-hint{{ $suffix ?? '' }}">Tanpa APK. Android (Chrome): ketuk <b class="text-slate-500">Pasang Sekarang</b> / menu ⋮ &rarr; <b class="text-slate-500">Instal aplikasi</b>. iPhone (Safari): <b class="text-slate-500">Bagikan</b> &rarr; <b class="text-slate-500">Tambah ke Layar Utama</b>.</p>
    @elseif ($androidUrl)
        <p class="mt-3 text-[11px] leading-relaxed text-slate-400">Android: setelah unduhan selesai, ketuk file lalu pilih <b class="text-slate-500">Instal</b> (izinkan "Instal aplikasi tidak dikenal" bila diminta).</p>
    @endif
</div>
@if ($pwaLocal)
    @once
        <script>
            // Instal sekali ketuk: manifest + service worker PWA dipasang di halaman login, lalu tombol Android
            // memanggil dialog instal bawaan browser. Bila browser belum mengizinkan, tombol membuka /m untuk panduan.
            (function () {
                if (!('serviceWorker' in navigator)) return;
                var link = document.createElement('link');
                link.rel = 'manifest';
                link.href = @json($pwaUrl . 'manifest.json');
                document.head.appendChild(link);
                navigator.serviceWorker.register('/esurat-sw.js?app=' + encodeURIComponent(new URL(@json($pwaUrl)).pathname), { scope: '/' }).catch(function () {});
                var prompt = null;
                var label = function (t) { document.querySelectorAll('[data-pwa-label]').forEach(function (el) { el.textContent = t; }); };
                window.addEventListener('beforeinstallprompt', function (e) { e.preventDefault(); prompt = e; label('Instal sekarang'); });
                window.addEventListener('appinstalled', function () { prompt = null; label('Terpasang ✓'); });
                document.addEventListener('click', function (ev) {
                    var btn = ev.target.closest && ev.target.closest('[data-pwa-install]');
                    if (!btn || !prompt) return;
                    ev.preventDefault();
                    prompt.prompt();
                    prompt.userChoice.then(function (r) { if (r.outcome === 'accepted') label('Memasang…'); }).finally(function () { prompt = null; });
                });
            })();
        </script>
    @endonce
@endif
