@props(['user', 'size' => 'h-9 w-9 text-xs'])
@php
    $palette = ['bg-emerald-500/20 text-emerald-300', 'bg-sky-500/20 text-sky-300', 'bg-amber-500/20 text-amber-300', 'bg-fuchsia-500/20 text-fuchsia-300', 'bg-rose-500/20 text-rose-300', 'bg-violet-500/20 text-violet-300'];
    $tone = $palette[$user->id % count($palette)];
@endphp
<span {{ $attributes->merge(['class' => "inline-flex shrink-0 items-center justify-center rounded-full font-semibold {$size} {$tone}"]) }}>{{ $user->initials() }}</span>
