@props(['tone' => 'phosphor'])

@php
    $map = [
        'phosphor' => 'border border-phosphor/45 bg-phosphor/10 text-phosphor',
        'amber' => 'border border-amber/45 bg-amber/10 text-amber',
        'alert' => 'border border-alert/45 bg-alert/10 text-alert',
        'cyan' => 'border border-cyan/45 bg-cyan/10 text-cyan',
        'dim' => 'border border-static/35 bg-static/10 text-static',
    ];
    $cls = $map[$tone] ?? $map['phosphor'];
@endphp

<span
    {{ $attributes->merge(['class' => 'inline-flex min-h-6 items-center text-[11px] font-bold uppercase tracking-[0.1em] px-2 py-1 leading-none '.$cls]) }}
>
    {{ $slot }}
</span>
