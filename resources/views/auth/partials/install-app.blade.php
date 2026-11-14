@php $androidUrl = config('services.mobile.android_url'); $iosUrl = config('services.mobile.ios_url'); @endphp
<div class="rounded-2xl border p-4 sm:p-5 {{ $class ?? 'border-slate-200 bg-white shadow-sm' }}" data-testid="login-install-app{{ $suffix ?? '' }}">
    <div class="flex items-center gap-3">
        <span class="grid place-items-center w-10 h-10 shrink-0 rounded-xl bg-ruby-50 text-ruby-700"><i data-lucide="bell-ring" class="w-5 h-5"></i></span>
        <div class="min-w-0">
            <p class="text-sm font-bold text-maroon-950">Instal aplikasi E-Surat</p>
            <p class="text-xs text-slate-500">Notifikasi surat &amp; disposisi langsung ke HP</p>
        </div>
    </div>
    <div class="mt-4 grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)] gap-2.5">
        @if ($androidUrl)
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
        @if ($iosUrl)
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
    @if ($androidUrl)
        <p class="mt-3 text-[11px] leading-relaxed text-slate-400">Android: setelah unduhan selesai, ketuk file lalu pilih <b class="text-slate-500">Instal</b> (izinkan "Instal aplikasi tidak dikenal" bila diminta).</p>
    @endif
</div>
