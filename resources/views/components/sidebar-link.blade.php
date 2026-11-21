@props(['href', 'active' => false, 'label', 'badge' => null, 'badgeVariant' => 'accent'])

@php
    $showBadge = is_numeric($badge) && (int) $badge > 0;
    $badgeText = $showBadge ? ((int) $badge > 9 ? '9+' : (string) (int) $badge) : '';
    $badgeClass = 'sidebar-notify-badge'.($badgeVariant === 'danger' ? ' sidebar-notify-badge-danger' : '');
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'sidebar-link'.($active ? ' sidebar-link-active' : '')]) }}>
    <span class="sidebar-link-main">
        <span class="sidebar-link-icon">
            {!! $icon ?? '' !!}
            @if($showBadge && $badgeVariant === 'danger')
                <span class="sidebar-notify-dot" aria-hidden="true"></span>
            @endif
        </span>
        <span class="sidebar-link-label">{{ $label ?? $slot }}</span>
    </span>
    @if($showBadge)
        <span class="{{ $badgeClass }}">{{ $badgeText }}</span>
    @endif
</a>
