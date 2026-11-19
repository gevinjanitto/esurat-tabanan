<div {{ $attributes->merge(['class' => 'card p-10 sm:p-14 text-center']) }} data-reveal>
    <img src="{{ asset('images/envelope.webp') }}" alt="" class="mx-auto mb-5 w-36 animate-floaty drop-shadow-xl">
    <p class="font-display text-lg font-bold text-maroon-950">{{ $title }}</p>
    <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">{{ $slot }}</p>
</div>
