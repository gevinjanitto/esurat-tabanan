@php
    $c = $color ?? 'slate';
@endphp
<span {{ $attributes->merge(['class' => "badge badge-dot badge-{$c}"]) }}>{{ $slot }}</span>
