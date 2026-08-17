@props([
    'items' => [],
    'theme' => 'blue',
    'title' => 'Dashboard Sections',
    'footerLabel' => null,
    'footerValue' => null,
])

<aside class="role-dashboard-sidebar role-dashboard-sidebar-{{ $theme }}" aria-label="Navigasi bagian dashboard">
    <div class="role-sidebar-sticky">
        <div class="role-sidebar-title">{{ $title }}</div>

        <nav class="role-sidebar-nav">
            @foreach ($items as $item)
                <div class="role-sidebar-group">
                    <span class="role-sidebar-eyebrow">{{ $item['group'] }}</span>
                    <a class="role-sidebar-link {{ $loop->first ? 'active' : '' }}" href="#{{ $item['target'] }}" data-dashboard-section-link="{{ $item['target'] }}">
                        <i class="bi {{ $item['icon'] ?? 'bi-circle' }}" aria-hidden="true"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                </div>
            @endforeach
        </nav>

        @if ($footerLabel || $footerValue)
            <div class="role-sidebar-footer">
                @if ($footerLabel)<span>{{ $footerLabel }}</span>@endif
                @if ($footerValue)<strong>{{ $footerValue }}</strong>@endif
            </div>
        @endif
    </div>
</aside>
