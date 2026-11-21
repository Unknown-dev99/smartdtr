@props([
    'size' => 'md',
    'class' => '',
    'variant' => 'default',
])

@php
    $sizes = [
        'xs' => 'w-8 h-8',
        'sm' => 'w-10 h-10',
        'md' => 'w-12 h-12',
        'lg' => 'w-16 h-16',
        'xl' => 'w-20 h-20',
        '2xl' => 'w-24 h-24',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $variantClass = match ($variant) {
        'sidebar' => 'app-logo-sidebar',
        'light' => 'app-logo-light',
        default => '',
    };
@endphp

<img
    src="{{ asset('images/inhs-logo.png') }}"
    alt="Isabela National High School"
    {{ $attributes->merge(['class' => trim("app-logo $sizeClass object-contain shrink-0 $variantClass $class")]) }}
>
