@props([
    'badge',
    'title',
    'description',
    'icon' => 'bi-grid-1x2-fill',
])

<header {{ $attributes->class(['workspace-hero']) }}>
    <div class="workspace-hero-content">
        <div class="workspace-hero-heading">
            <span class="workspace-hero-icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
            <div>
                <span class="workspace-hero-badge">{{ $badge }}</span>
                <h1>{{ $title }}</h1>
                <p>{{ $description }}</p>
                @isset($actions)<div class="workspace-hero-actions">{{ $actions }}</div>@endisset
            </div>
        </div>
        @isset($meta)<aside class="workspace-hero-meta">{{ $meta }}</aside>@endisset
    </div>
</header>
