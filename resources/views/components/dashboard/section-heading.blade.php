@props([
    'eyebrow',
    'title',
    'description' => null,
    'headingId' => null,
])

<div {{ $attributes->class(['dashboard-section-heading']) }}>
    <div>
        <span class="section-eyebrow">{{ $eyebrow }}</span>
        <h2 @if ($headingId) id="{{ $headingId }}" @endif>{{ $title }}</h2>
        @if ($description)<p>{{ $description }}</p>@endif
    </div>
    @isset($action)<div class="dashboard-section-action">{{ $action }}</div>@endisset
</div>
