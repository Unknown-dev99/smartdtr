@props([
    'user',
    'size' => 'md',
    'class' => '',
])

@php
    $sizes = [
        'xs' => 'user-avatar-xs',
        'sm' => 'user-avatar-sm',
        'md' => 'user-avatar-md',
        'lg' => 'user-avatar-lg',
        'xl' => 'user-avatar-xl',
        '2xl' => 'user-avatar-2xl',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

@if($user->hasAvatar())
    <img
        src="{{ $user->avatarUrl() }}"
        alt="{{ $user->displayName() }}"
        {{ $attributes->merge(['class' => "user-avatar $sizeClass $class"]) }}
    >
@else
    <span {{ $attributes->merge(['class' => "user-avatar user-avatar-fallback $sizeClass $class"]) }} aria-hidden="true">
        {{ $user->initials() }}
    </span>
@endif
