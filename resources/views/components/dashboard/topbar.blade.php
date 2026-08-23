@props([
    'homeUrl',
    'roleLabel',
    'context' => null,
    'links' => [],
])

@php
    $dashboardUser = auth()->user();
    $navigationId = 'dashboard-navigation-'.\Illuminate\Support\Str::slug($roleLabel);
    $initials = \Illuminate\Support\Str::of($dashboardUser?->name ?? 'SIGAP WARGA')
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('');
@endphp

<nav class="navbar navbar-expand-xl dashboard-topbar sticky-top" aria-label="Navigasi dashboard {{ $roleLabel }}">
    <div class="container dashboard-topbar-inner">
        <a class="navbar-brand dashboard-brand" href="{{ $homeUrl }}" aria-label="SIGAP WARGA - {{ $roleLabel }}">
            <span class="dashboard-brand-mark" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
            <span class="dashboard-brand-copy">
                <strong>SIGAP WARGA</strong>
                <small>{{ $roleLabel }}</small>
            </span>
        </a>

        <button class="navbar-toggler dashboard-menu-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $navigationId }}" aria-controls="{{ $navigationId }}" aria-expanded="false" aria-label="Buka menu dashboard">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>

        <div id="{{ $navigationId }}" class="collapse navbar-collapse">
            @if (count($links) > 0)
                <ul class="navbar-nav dashboard-nav mx-xl-auto py-3 py-xl-0">
                    @foreach ($links as $link)
                        <li class="nav-item">
                            <a class="nav-link dashboard-nav-link {{ ($link['active'] ?? false) ? 'active' : '' }}" href="{{ $link['url'] }}" @if($link['active'] ?? false) aria-current="page" @endif>
                                @if (!empty($link['icon']))<i class="bi {{ $link['icon'] }}" aria-hidden="true"></i>@endif
                                <span>{{ $link['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="dropdown dashboard-account ms-xl-auto">
                <button class="dashboard-account-button dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="dashboard-avatar" aria-hidden="true">{{ $initials ?: 'SW' }}</span>
                    <span class="dashboard-account-copy">
                        <strong>{{ $dashboardUser?->name ?? 'Petugas' }}</strong>
                        <small>{{ $context ?: $roleLabel }}</small>
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu-end dashboard-account-menu">
                    <div class="px-3 py-2 border-bottom">
                        <span class="small text-secondary d-block">Masuk sebagai</span>
                        <strong class="small">{{ $roleLabel }}</strong>
                    </div>
                    <a class="dropdown-item" href="{{ route('public.home') }}"><i class="bi bi-house-door me-2" aria-hidden="true"></i>Portal warga</a>
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Keluar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</nav>
