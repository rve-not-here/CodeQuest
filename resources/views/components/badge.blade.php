@props(['tone' => 'phosphor'])

@php
    $map = [
        'phosphor' => 'border-phosphor/40 bg-phosphor/10 text-phosphor',
        'amber' => 'border-amber/40 bg-amber/10 text-amber',
        'alert' => 'border-alert/40 bg-alert/10 text-alert',
        'cyan' => 'border-cyan/40 bg-cyan/10 text-cyan',
        'dim' => 'border-static/30 bg-static/5 text-static',
    ];
    $cls = $map[$tone] ?? $map['phosphor'];
@endphp

{{-- Status marker, not a chip for its own sake: no fill beyond a tint,
     no rounding beyond 2px, and a monospace label so states sort in a
     column when several sit side by side. --}}
<span
    {{ $attributes->merge(['class' => 'inline-flex min-h-[1.5rem] items-center border px-1.5 py-0.5 font-code text-[0.6875rem] font-bold uppercase leading-none tracking-[0.08em] '.$cls]) }}
>
    {{ $slot }}
</span>
