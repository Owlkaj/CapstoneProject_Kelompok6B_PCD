@props([
    'color' => 'emerald',
    'label' => '',
])

@php
    $tones = [
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'red' => 'bg-red-50 text-red-700 ring-red-600/20',
        'orange' => 'bg-orange-50 text-orange-700 ring-orange-600/20',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        'lime' => 'bg-lime-50 text-lime-700 ring-lime-600/20',
        'pink' => 'bg-pink-50 text-pink-700 ring-pink-600/20',
        'purple' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
        'stone' => 'bg-stone-100 text-stone-700 ring-stone-600/20',
        'cyan' => 'bg-cyan-50 text-cyan-700 ring-cyan-600/20',
        'yellow' => 'bg-yellow-50 text-yellow-800 ring-yellow-600/20',
    ];

    $tone = $tones[$color] ?? $tones['emerald'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {$tone}"]) }}>
    {{ $label }}
</span>
