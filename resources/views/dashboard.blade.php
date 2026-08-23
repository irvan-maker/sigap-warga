@extends('layouts.app')

@section('title', 'Dashboard Admin - SIGAP WARGA')

@section('content')
    @php
        $admin = auth()->user();
        $navigation = [
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bi-grid-1x2', 'active' => true],
            ['label' => 'Laporan', 'url' => route('admin.reports.index'), 'icon' => 'bi-inbox'],
            ['label' => 'Petugas', 'url' => route('admin.users.index'), 'icon' => 'bi-people'],
            ['label' => 'QR Wilayah', 'url' => route('admin.service-entry-points.index'), 'icon' => 'bi-qr-code'],
            ['label' => 'WhatsApp', 'url' => route('admin.whatsapp-integration.index'), 'icon' => 'bi-whatsapp'],
            ['label' => 'Master Surat', 'url' => route('kelurahan.letter-types.index'), 'icon' => 'bi-sliders'],
        ];
        $sectionNavigation = [
            ['group' => 'Cakupan layanan', 'label' => 'Statistik Utama', 'target' => 'coverage-heading', 'icon' => 'bi-bar-chart'],
            ['group' => 'Progres penanganan', 'label' => 'Status Laporan', 'target' => 'status-heading', 'icon' => 'bi-list-check'],
            ['group' => 'Operasional', 'label' => 'Aksi Cepat Administrator', 'target' => 'admin-actions-heading', 'icon' => 'bi-lightning-charge'],
            ['group' => 'Lintas wilayah', 'label' => 'Laporan Terbaru', 'target' => 'laporan-terbaru', 'icon' => 'bi-file-earmark-text'],
            ['group' => 'Analitik laporan', 'label' => 'Tren dan Distribusi', 'target' => 'analytics-heading', 'icon' => 'bi-graph-up-arrow'],
            ['group' => 'Sebaran layanan', 'label' => 'Ringkasan Laporan per RT', 'target' => 'ringkasan-wilayah', 'icon' => 'bi-geo-alt'],
            ['group' => 'Kinerja wilayah', 'label' => 'Ranking RT', 'target' => 'ranking-title', 'icon' => 'bi-trophy'],
        ];
    @endphp

    <x-dashboard.role-dashboard-styles />

    <div class="dashboard-workspace admin-dashboard">
        <x-dashboard.topbar :home-url="route('dashboard')" role-label="Super Admin" context="Pusat kendali sistem" :links="$navigation" />

        <div class="role-dashboard-shell">
            <x-dashboard.section-sidebar
                :items="$sectionNavigation"
                theme="blue"
                title="Dashboard Sections"
                footer-label="Periode Data"
                :footer-value="now()->locale('id')->isoFormat('D MMMM Y')"
            />

        <main id="main-content" class="role-dashboard-content dashboard-main">
            <x-dashboard.hero badge="Pusat Kendali" title="Selamat datang, {{ $admin->name }}" description="Pantau kesiapan layanan, cakupan wilayah, dan progres laporan dari satu ruang kerja.">
                <x-slot:meta><small class="d-block mb-1">Ringkasan sistem</small><strong class="d-block">Dashboard Administrator</strong><span class="small text-white-50">{{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}</span></x-slot:meta>
                <x-slot:actions><a class="btn btn-light" href="{{ route('admin.whatsapp-integration.index') }}"><i class="bi bi-whatsapp me-2" aria-hidden="true"></i>Cek Integrasi WhatsApp</a></x-slot:actions>
            </x-dashboard.hero>

            <section class="module-strip" aria-label="Status modul sistem">
                <div class="module-strip-copy"><span class="module-strip-icon"><i class="bi bi-boxes" aria-hidden="true"></i></span><div><strong class="d-block">Tahap Uji Lokal</strong><small class="text-secondary">Laporan cepat aktif; modul lain tetap sebagai prototype.</small></div></div>
                <div class="module-pills">
                    <span class="module-pill module-pill-active">Laporan Cepat · PILOT</span>
                    <span class="module-pill module-pill-prototype">Sensus · PROTOTYPE</span>
                    <span class="module-pill module-pill-prototype">Posyandu · PROTOTYPE</span>
                    <span class="module-pill module-pill-prototype">Persuratan · PROTOTYPE</span>
                    <span class="module-pill module-pill-disabled">Darurat · NONAKTIF</span>
                </div>
            </section>

            <section class="dashboard-section" aria-labelledby="coverage-heading">
                <x-dashboard.section-heading eyebrow="Cakupan layanan" title="Statistik Utama" description="Gambaran singkat data wilayah dan aktivitas laporan." heading-id="coverage-heading" />
                <div class="row g-3">
                    <div class="col-sm-6 col-xl-3"><x-dashboard.metric label="Total Warga" :value="number_format($totalCitizens)" helper="Warga terdaftar" icon="bi-people" tone="primary" /></div>
                    <div class="col-sm-6 col-xl-3"><x-dashboard.metric label="RW Aktif" :value="number_format($totalActiveRws)" helper="Wilayah RW aktif" icon="bi-diagram-3" tone="success" /></div>
                    <div class="col-sm-6 col-xl-3"><x-dashboard.metric label="RT Aktif" :value="number_format($totalActiveRts)" helper="Wilayah RT aktif" icon="bi-geo-alt" tone="warning" /></div>
                    <div class="col-sm-6 col-xl-3"><x-dashboard.metric label="Total Laporan" :value="number_format($totalReports)" helper="Semua laporan warga" icon="bi-inbox" tone="info" :href="route('admin.reports.index')" /></div>
                </div>
            </section>

            <section class="dashboard-section" aria-labelledby="status-heading">
                <x-dashboard.section-heading eyebrow="Progres penanganan" title="Status Laporan" description="Komposisi status dari {{ number_format($totalReports) }} laporan tercatat." heading-id="status-heading" />
                <div class="row g-3">
                    @foreach (\App\Enums\ReportStatus::cases() as $status)
                        @php
                            $statusTotal = $totalsByStatus[$status->value];
                            $percentage = $totalReports > 0 ? ($statusTotal / $totalReports) * 100 : 0;
                        @endphp
                        <div class="col-sm-6 col-xl-3">
                            <div class="card dashboard-panel-modern h-100">
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

            <section class="dashboard-section" aria-labelledby="admin-actions-heading">
                <x-dashboard.section-heading eyebrow="Operasional" title="Aksi Cepat Administrator" description="Jalur langsung menuju pekerjaan yang paling sering digunakan." heading-id="admin-actions-heading" />
                <div class="row g-3">
                    <div class="col-sm-6 col-xl-3"><x-dashboard.action-card :href="route('admin.reports.index')" title="Kelola Laporan" description="Cari dan tinjau seluruh laporan" icon="bi-inbox" /></div>
                    <div class="col-sm-6 col-xl-3"><x-dashboard.action-card :href="route('admin.users.index')" title="Kelola Akun Petugas" description="Atur akun dan hak akses" icon="bi-people" tone="success" /></div>
                    <div class="col-sm-6 col-xl-3"><x-dashboard.action-card :href="route('admin.service-entry-points.index')" title="Atur QR Wilayah" description="Kelola satu QR resmi untuk setiap RT" icon="bi-qr-code" tone="warning" /></div>
                    <div class="col-sm-6 col-xl-3"><x-dashboard.action-card :href="route('admin.whatsapp-integration.index')" title="Integrasi WhatsApp" description="Periksa kesiapan Meta dan webhook" icon="bi-whatsapp" tone="success" /></div>
                </div>
            </section>

            <section class="mb-4" aria-labelledby="analytics-heading">
                <div class="mb-3">
                    <p class="admin-eyebrow mb-1">Analitik laporan</p>
                    <h2 id="analytics-heading" class="h4 fw-bold mb-0">Tren dan Distribusi</h2>
                </div>
                <div class="row g-4">
                    <div class="col-xl-8">
                        <div class="card admin-panel h-100 border-0 shadow-sm">
                            <div class="card-body p-4">
                                <h3 class="h5 fw-bold mb-1">Laporan per Bulan</h3>
                                <p class="text-secondary small mb-4">Jumlah laporan dalam 6 bulan terakhir</p>
                                <div class="admin-chart-container">
                                    <canvas id="monthlyReportsChart" aria-label="Grafik jumlah laporan per bulan" role="img"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="card admin-panel h-100 border-0 shadow-sm">
                            <div class="card-body p-4">
                                <h3 class="h5 fw-bold mb-1">Distribusi Status</h3>
                                <p class="text-secondary small mb-4">Komposisi seluruh status laporan</p>
                                <div class="admin-chart-container">
                                    <canvas id="reportStatusChart" aria-label="Grafik distribusi status laporan" role="img"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
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
            @include('analytics.village')
        </main>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const monthlyCanvas = document.getElementById('monthlyReportsChart');
            const statusCanvas = document.getElementById('reportStatusChart');

            if (!monthlyCanvas || !statusCanvas || typeof window.Chart === 'undefined') {
                return;
            }

            new window.Chart(monthlyCanvas, {
                type: 'bar',
                data: {
                    labels: @json($monthlyReportChart['labels']),
                    datasets: [{
                        label: 'Jumlah laporan',
                        data: @json($monthlyReportChart['data']),
                        backgroundColor: 'rgba(13, 110, 253, 0.78)',
                        borderColor: '#0d6efd',
                        borderWidth: 1,
                        borderRadius: 6,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                            grid: { color: 'rgba(23, 32, 51, 0.08)' },
                        },
                        x: {
                            grid: { display: false },
                        },
                    },
                },
            });

            new window.Chart(statusCanvas, {
                type: 'doughnut',
                data: {
                    labels: @json($reportStatusChart['labels']),
                    datasets: [{
                        data: @json($reportStatusChart['data']),
                        backgroundColor: ['#0d6efd', '#ffc107', '#0dcaf0', '#198754', '#dc3545'],
                        borderColor: '#ffffff',
                        borderWidth: 3,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '64%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 16,
                                usePointStyle: true,
                            },
                        },
                    },
                },
            });
        });
    </script>
@endpush
