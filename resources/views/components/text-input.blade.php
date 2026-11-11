@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-lg border-zinc-700 bg-zinc-950 text-white placeholder-zinc-500 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 disabled:opacity-50']) }}>
