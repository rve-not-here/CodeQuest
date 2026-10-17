@props(['tone' => 'phosphor'])

@php
    $map = [
        'phosphor' => 'border border-accent-line/45 bg-accent/10 text-accent',
        'amber' => 'border border-warning/45 bg-warning/10 text-warning',
        'alert' => 'border border-danger/45 bg-danger/10 text-danger',
        'cyan' => 'border border-info/45 bg-info/10 text-info',
        'success' => 'border border-success/45 bg-success/10 text-success',
        'dim' => 'border border-static/35 bg-static/10 text-static',
    ];
    $cls = $map[$tone] ?? $map['phosphor'];
@endphp

<span
    {{ $attributes->merge(['class' => 'inline-flex min-h-6 items-center text-[11px] font-bold uppercase tracking-[0.1em] px-2 py-1 leading-none '.$cls]) }}
>
    {{ $slot }}
</span>
