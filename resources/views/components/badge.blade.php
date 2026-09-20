@props(['color' => 'gray'])

@php
$colors = [
    'gray' => 'bg-gray-100 text-gray-700',
    'blue' => 'bg-blue-100 text-blue-700',
    'amber' => 'bg-amber-100 text-amber-800',
    'orange' => 'bg-orange-100 text-orange-700',
    'yellow' => 'bg-yellow-100 text-yellow-800',
    'red' => 'bg-red-100 text-red-700',
    'green' => 'bg-green-100 text-green-700',
    'purple' => 'bg-purple-100 text-purple-700',
];
$class = $colors[$color] ?? $colors['gray'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium {$class}"]) }}>
    {{ $slot }}
</span>
