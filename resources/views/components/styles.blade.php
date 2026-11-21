{{-- Loads compiled CSS/JS; works with XAMPP subdirectory deploy --}}
@php
    $manifestPath = public_path('build/manifest.json');
    $manifest = is_file($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : [];
    $cssEntry = $manifest['resources/css/app.css']['file'] ?? null;
    $jsEntry = $manifest['resources/js/app.js']['file'] ?? null;
@endphp

@if (file_exists(public_path('hot')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@elseif ($cssEntry)
    <link rel="stylesheet" href="{{ asset('build/'.$cssEntry) }}">
    @if ($jsEntry)
        <script type="module" src="{{ asset('build/'.$jsEntry) }}"></script>
    @endif
@else
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@endif
