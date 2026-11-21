@props(['title', 'subtitle' => null])

<div class="page-header">
    <h1 class="page-title">{{ $title }}</h1>
    @if($subtitle)
        <p class="page-subtitle">{{ $subtitle }}</p>
    @endif
    @if(isset($actions))
        <div class="page-actions">{{ $actions }}</div>
    @endif
</div>
