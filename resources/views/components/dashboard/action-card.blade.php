@props([
    'href',
    'title',
    'description',
    'icon' => 'bi-arrow-right-circle',
    'tone' => 'primary',
    'badge' => null,
])

<a href="{{ $href }}" {{ $attributes->class(['dashboard-action-card dashboard-action-'.$tone]) }}>
    <span class="dashboard-action-icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
    <span class="dashboard-action-copy">
        <span class="d-flex align-items-center gap-2 flex-wrap">
            <strong>{{ $title }}</strong>
            @if ($badge)<span class="badge rounded-pill text-bg-light">{{ $badge }}</span>@endif
        </span>
        <small>{{ $description }}</small>
    </span>
    <i class="bi bi-chevron-right dashboard-action-arrow" aria-hidden="true"></i>
</a>
