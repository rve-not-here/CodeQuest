@props(['tone' => 'phosphor'])

@php
    $map = [
        'phosphor' => 'bg-[#f0f0f5] text-[#3b3b4f]',
        'amber' => 'bg-[#fff3cd] text-[#8a6d1a]',
        'alert' => 'bg-[#fde8e7] text-[#d22d20]',
        'cyan' => 'bg-[#d9f0ff] text-[#1376d0]',
        'dim' => 'bg-[#fafafc] text-[#6f6f79] border border-phosphor-dim/70',
    ];
    $cls = $map[$tone] ?? $map['phosphor'];
@endphp

<span
    {{ $attributes->merge(['class' => 'inline-block text-[11px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-[2px] leading-none '.$cls]) }}
>
    {{ $slot }}
</span>