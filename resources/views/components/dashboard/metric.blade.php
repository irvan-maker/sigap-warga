@props([
    'label',
    'value',
    'helper' => null,
    'icon' => 'bi-bar-chart',
    'tone' => 'primary',
    'href' => null,
])

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class(['dashboard-metric dashboard-metric-link dashboard-metric-'.$tone]) }}>
@else
    <div {{ $attributes->class(['dashboard-metric dashboard-metric-'.$tone]) }}>
@endif
        <span class="dashboard-metric-icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
        <span class="dashboard-metric-copy">
            <span class="dashboard-metric-label">{{ $label }}</span>
            <strong class="dashboard-metric-value">{{ $value }}</strong>
            @if ($helper)<small>{{ $helper }}</small>@endif
        </span>
        @if ($href)<i class="bi bi-arrow-up-right dashboard-metric-arrow" aria-hidden="true"></i>@endif
@if ($href)
    </a>
@else
    </div>
@endif
