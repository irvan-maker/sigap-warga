@extends('layouts.app')

@section('title', 'Dashboard Admin - SIGAP WARGA')

@section('content')
    @php
        $admin = auth()->user();
    @endphp

    <div class="admin-dashboard min-vh-100">
        <nav class="navbar bg-white border-bottom sticky-top" aria-label="Navigasi admin">
            <div class="container py-1">
                <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-primary" href="{{ route('dashboard') }}">
                    <span class="admin-brand-mark d-inline-flex align-items-center justify-content-center rounded-3 text-white" aria-hidden="true">SW</span>
                    <span>SIGAP WARGA</span>
                </a>
                <div class="d-flex align-items-center gap-3">
                    <div class="d-none d-sm-block text-end lh-sm">
                        <span class="small fw-semibold d-block">{{ $admin->name }}</span>
                        <span class="text-secondary small">Administrator</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm px-3">Keluar</button>
                    </form>
                </div>
            </div>
        </nav>

        <main class="container py-4 py-lg-5">
            <header class="admin-hero position-relative overflow-hidden rounded-4 p-4 p-lg-5 mb-4 text-white shadow-sm">
                <div class="position-relative d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-4">
                    <div>
                        <span class="badge rounded-pill bg-white bg-opacity-10 border border-white border-opacity-25 px-3 py-2 mb-3">Pusat Kendali</span>
                        <p class="text-white-50 mb-1">Selamat datang,</p>
                        <h1 class="display-6 fw-bold mb-3">{{ $admin->name }}</h1>
                        <p class="admin-text-muted mb-0">Pantau layanan warga, cakupan wilayah, dan progres penanganan laporan SIGAP WARGA.</p>
                    </div>
                    <div class="admin-hero-meta rounded-4 p-3 p-lg-4">
                        <div class="small text-white-50 text-uppercase fw-semibold mb-1">Ringkasan sistem</div>
                        <div class="h5 fw-bold mb-2">Dashboard Administrator</div>
                        <div class="admin-text-muted small">{{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}</div>
                    </div>
                </div>
            </header>

            <section class="mb-4" aria-labelledby="coverage-heading">
                <div class="mb-3">
                    <p class="admin-eyebrow mb-1">Cakupan layanan</p>
                    <h2 id="coverage-heading" class="h4 fw-bold mb-0">Statistik Utama</h2>
                </div>
                <div class="row g-3">
                    @foreach ([
                        ['label' => 'Total Warga', 'value' => $totalCitizens, 'hint' => 'Warga terdaftar', 'initial' => 'W', 'color' => 'primary'],
                        ['label' => 'RW Aktif', 'value' => $totalActiveRws, 'hint' => 'Wilayah RW aktif', 'initial' => 'RW', 'color' => 'success'],
                        ['label' => 'RT Aktif', 'value' => $totalActiveRts, 'hint' => 'Wilayah RT aktif', 'initial' => 'RT', 'color' => 'warning'],
                        ['label' => 'Total Laporan', 'value' => $totalReports, 'hint' => 'Semua laporan warga', 'initial' => 'L', 'color' => 'info'],
                    ] as $metric)
                        <div class="col-sm-6 col-xl-3">
                            <div class="card admin-metric-card h-100 border-0 shadow-sm">
                                <div class="card-body p-4 d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <div class="text-secondary small text-uppercase fw-semibold">{{ $metric['label'] }}</div>
                                        <div class="display-6 fw-bold mt-2 mb-1">{{ number_format($metric['value']) }}</div>
                                        <div class="text-secondary small">{{ $metric['hint'] }}</div>
                                    </div>
                                    <span class="admin-metric-icon bg-{{ $metric['color'] }}-subtle text-{{ $metric['color'] }}" aria-hidden="true">{{ $metric['initial'] }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="mb-4" aria-labelledby="status-heading">
                <div class="d-flex justify-content-between align-items-end mb-3">
                    <div>
                        <p class="admin-eyebrow mb-1">Progres penanganan</p>
                        <h2 id="status-heading" class="h4 fw-bold mb-0">Status Laporan</h2>
                    </div>
                    <span class="text-secondary small d-none d-sm-inline">{{ number_format($totalReports) }} laporan tercatat</span>
                </div>
                <div class="row g-3">
                    @foreach (\App\Enums\ReportStatus::cases() as $status)
                        @php
                            $statusTotal = $totalsByStatus[$status->value];
                            $percentage = $totalReports > 0 ? ($statusTotal / $totalReports) * 100 : 0;
                        @endphp
                        <div class="col-sm-6 col-xl-3">
                            <div class="card admin-status-card h-100 border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <div class="text-secondary small text-uppercase fw-semibold">{{ $status->label() }}</div>
                                            <div class="display-6 fw-bold mt-2 mb-1">{{ number_format($statusTotal) }}</div>
                                            <div class="text-secondary small">{{ number_format($percentage, 1) }}% dari total</div>
                                        </div>
                                        <span class="admin-metric-icon bg-{{ $status->bootstrapColor() }}-subtle text-{{ $status->bootstrapColor() }}" aria-hidden="true">{{ $status->initial() }}</span>
                                    </div>
                                    <div class="progress mt-3" role="progressbar" aria-label="Persentase laporan {{ $status->label() }}" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar bg-{{ $status->bootstrapColor() }}" style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="row g-4 mb-4">
                <div class="col-xl-8">
                    <section id="laporan-terbaru" class="card admin-panel h-100 border-0 shadow-sm" aria-labelledby="latest-reports-heading">
                        <div class="card-header bg-white border-0 p-4 pb-2">
                            <p class="admin-eyebrow mb-1">Lintas wilayah</p>
                            <h2 id="latest-reports-heading" class="h5 fw-bold mb-0">Laporan Terbaru</h2>
                        </div>
                        <div class="card-body p-0 pt-2">
                            @if ($latestReports->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr><th class="ps-4">Tiket</th><th>Laporan</th><th>Wilayah</th><th>Status</th><th class="pe-4">Tanggal</th></tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($latestReports as $report)
                                                <tr>
                                                    <td class="ps-4"><a class="fw-semibold text-decoration-none text-nowrap" href="{{ route('reports.show', $report) }}">{{ $report->ticket_number }}</a></td>
                                                    <td style="min-width: 14rem"><strong class="d-block text-body">{{ $report->title }}</strong><small class="text-secondary">{{ $report->citizen?->name ?? 'Warga tidak tersedia' }}</small></td>
                                                    <td class="text-nowrap">{{ $report->rt?->code ?? 'RT —' }} · {{ $report->rt?->rw?->code ?? 'RW —' }}</td>
                                                    <td><span class="badge rounded-pill text-bg-{{ $report->status->bootstrapColor() }} px-3 py-2">{{ $report->status->label() }}</span></td>
                                                    <td class="pe-4 text-nowrap">{{ $report->reported_at?->format('d M Y') ?? '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="admin-empty-state text-center px-4 py-5 mx-auto">
                                    <div class="admin-empty-icon mx-auto mb-3" aria-hidden="true">L</div>
                                    <h3 class="h6 fw-bold">Belum ada laporan</h3>
                                    <p class="text-secondary small mb-3">Laporan terbaru dari seluruh wilayah akan ditampilkan di sini.</p>
                                    <a class="btn btn-primary btn-sm" href="{{ route('reports.create') }}">Buat laporan pertama</a>
                                </div>
                            @endif
                        </div>
                    </section>
                </div>

                <div class="col-xl-4">
                    <section class="card admin-panel h-100 border-0 shadow-sm" aria-labelledby="quick-actions-heading">
                        <div class="card-body p-4">
                            <p class="admin-eyebrow mb-1">Navigasi</p>
                            <h2 id="quick-actions-heading" class="h5 fw-bold mb-4">Aksi Cepat</h2>
                            <div class="d-grid gap-3">
                                <a class="admin-quick-action d-flex align-items-center gap-3 rounded-3 border p-3 text-decoration-none" href="{{ route('reports.create') }}">
                                    <span class="admin-action-icon bg-primary-subtle text-primary" aria-hidden="true">01</span>
                                    <span><strong class="d-block text-body">Buat laporan</strong><small class="text-secondary">Catat laporan warga secara manual</small></span>
                                </a>
                                <a class="admin-quick-action d-flex align-items-center gap-3 rounded-3 border p-3 text-decoration-none" href="{{ route('tracking.index') }}">
                                    <span class="admin-action-icon bg-success-subtle text-success" aria-hidden="true">02</span>
                                    <span><strong class="d-block text-body">Lacak tiket</strong><small class="text-secondary">Buka halaman pelacakan publik</small></span>
                                </a>
                                <a class="admin-quick-action d-flex align-items-center gap-3 rounded-3 border p-3 text-decoration-none" href="#ringkasan-wilayah">
                                    <span class="admin-action-icon bg-warning-subtle text-warning-emphasis" aria-hidden="true">03</span>
                                    <span><strong class="d-block text-body">Lihat wilayah</strong><small class="text-secondary">Tinjau ringkasan laporan per RT</small></span>
                                </a>
                                <a class="admin-quick-action d-flex align-items-center gap-3 rounded-3 border p-3 text-decoration-none" href="#aktivitas-terbaru">
                                    <span class="admin-action-icon bg-info-subtle text-info-emphasis" aria-hidden="true">04</span>
                                    <span><strong class="d-block text-body">Pantau aktivitas</strong><small class="text-secondary">Lihat perubahan status terakhir</small></span>
                                </a>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-xl-7">
                    <section id="ringkasan-wilayah" class="card admin-panel h-100 border-0 shadow-sm" aria-labelledby="region-summary-heading">
                        <div class="card-header bg-white border-0 p-4 pb-2">
                            <p class="admin-eyebrow mb-1">Sebaran layanan</p>
                            <h2 id="region-summary-heading" class="h5 fw-bold mb-0">Ringkasan Laporan per RT</h2>
                        </div>
                        <div class="card-body p-0 pt-2">
                            @if ($reportSummaryByRt->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4">Wilayah</th>
                                                <th class="text-center">Total</th>
                                                @foreach (\App\Enums\ReportStatus::cases() as $status)
                                                    <th class="text-center {{ $loop->last ? 'pe-4' : '' }}">{{ $status->label() }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($reportSummaryByRt as $rt)
                                                <tr>
                                                    <td class="ps-4"><strong class="d-block">{{ $rt->code }}</strong><small class="text-secondary">{{ $rt->rw?->code ?? 'RW tidak tersedia' }}</small></td>
                                                    <td class="text-center fw-bold">{{ number_format($rt->reports_count) }}</td>
                                                    @foreach (\App\Enums\ReportStatus::cases() as $status)
                                                        @php
                                                            $countAttribute = strtolower($status->value).'_reports_count';
                                                        @endphp
                                                        <td class="text-center {{ $loop->last ? 'pe-4' : '' }}">{{ number_format($rt->{$countAttribute}) }}</td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="admin-empty-state text-center px-4 py-5 mx-auto">
                                    <div class="admin-empty-icon mx-auto mb-3" aria-hidden="true">RT</div>
                                    <h3 class="h6 fw-bold">Belum ada wilayah RT</h3>
                                    <p class="text-secondary small mb-0">Ringkasan laporan akan tersedia setelah data wilayah ditambahkan.</p>
                                </div>
                            @endif
                        </div>
                    </section>
                </div>

                <div class="col-xl-5">
                    <section id="aktivitas-terbaru" class="card admin-panel h-100 border-0 shadow-sm" aria-labelledby="activity-heading">
                        <div class="card-body p-4">
                            <p class="admin-eyebrow mb-1">Jejak operasional</p>
                            <h2 id="activity-heading" class="h5 fw-bold mb-4">Aktivitas Status Terbaru</h2>
                            @forelse ($latestActivities as $activity)
                                <div class="d-flex gap-3 {{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                                    <span class="admin-activity-dot bg-{{ $activity->new_status->bootstrapColor() }} mt-2 flex-shrink-0" aria-hidden="true"></span>
                                    <div class="min-w-0">
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                            @if ($activity->report)
                                                <a class="fw-semibold text-body text-decoration-none text-truncate" href="{{ route('reports.show', $activity->report) }}">{{ $activity->report->ticket_number }}</a>
                                            @else
                                                <span class="fw-semibold text-body">Laporan tidak tersedia</span>
                                            @endif
                                            <span class="badge rounded-pill text-bg-{{ $activity->new_status->bootstrapColor() }}">{{ $activity->new_status->label() }}</span>
                                        </div>
                                        <small class="text-secondary d-block text-truncate">{{ $activity->report?->title ?? 'Detail laporan tidak tersedia' }}</small>
                                        <small class="text-secondary">{{ $activity->user?->name ?? 'Sistem' }} · {{ $activity->created_at?->locale('id')->diffForHumans() ?? 'Waktu tidak tersedia' }}</small>
                                    </div>
                                </div>
                            @empty
                                <div class="admin-empty-state text-center py-4 mx-auto">
                                    <div class="admin-empty-icon mx-auto mb-3" aria-hidden="true">A</div>
                                    <h3 class="h6 fw-bold">Belum ada aktivitas status</h3>
                                    <p class="text-secondary small mb-0">Perubahan status laporan akan tercatat dan tampil di panel ini.</p>
                                </div>
                            @endforelse
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
@endsection
