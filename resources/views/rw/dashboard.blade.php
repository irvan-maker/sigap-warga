@extends('layouts.app')

@section('title', 'Dashboard RW - SIGAP WARGA')

@section('content')
@php
    $user = auth()->user();
    $hasActiveFilters = request()->filled('rt_id') || request()->filled('status') || request()->filled('search');
    $latestReports = $reports->getCollection()->take(5);
    $statusCases = collect(\App\Enums\ReportStatus::cases());
    $newCount = (int) ($totalsByStatus[\App\Enums\ReportStatus::NEW->value] ?? 0);
    $processingCount = (int) ($totalsByStatus[\App\Enums\ReportStatus::PROCESSING->value] ?? 0);
    $forwardedCount = (int) ($totalsByStatus[\App\Enums\ReportStatus::FORWARDED->value] ?? 0);
    $completedCount = (int) ($totalsByStatus[\App\Enums\ReportStatus::COMPLETED->value] ?? 0);
    $rejectedCount = (int) ($totalsByStatus[\App\Enums\ReportStatus::REJECTED->value] ?? 0);
    $attentionCount = $newCount + $forwardedCount;
    $maxRtReports = max(1, (int) collect($totalsByRt)->max());
    $rankings = $analytics['rankings']->sortByDesc('activity')->values();

    $navigation = [
        ['label' => 'Dashboard', 'url' => route('rw.dashboard'), 'icon' => 'bi-grid-1x2', 'active' => true],
        ['label' => 'Laporan', 'url' => '#antrean-laporan', 'icon' => 'bi-inbox'],
        ['label' => 'Wilayah RT', 'url' => route('rw.rts.index'), 'icon' => 'bi-diagram-3'],
        ['label' => 'Warga', 'url' => route('rw.citizens.index'), 'icon' => 'bi-people'],
        ['label' => 'Kartu Keluarga', 'url' => route('rw.family-cards.index'), 'icon' => 'bi-card-heading'],
        ['label' => 'Surat', 'url' => route('rw.letters.index'), 'icon' => 'bi-envelope-check'],
    ];

    $sectionNavigation = [
        ['group' => 'Cakupan wilayah', 'label' => 'Statistik Utama', 'target' => 'rw-statistik', 'icon' => 'bi-bar-chart'],
        ['group' => 'Perlu perhatian', 'label' => 'Antrean Prioritas', 'target' => 'rw-perhatian', 'icon' => 'bi-exclamation-triangle'],
        ['group' => 'Eskalasi', 'label' => 'Eskalasi dari RT', 'target' => 'rw-eskalasi', 'icon' => 'bi-arrow-up-right-circle'],
        ['group' => 'Operasional', 'label' => 'Aksi Cepat RW', 'target' => 'rw-operasional', 'icon' => 'bi-lightning-charge'],
        ['group' => 'Lintas RT', 'label' => 'Laporan Terbaru', 'target' => 'rw-terbaru', 'icon' => 'bi-inboxes'],
        ['group' => 'Progres penanganan', 'label' => 'Status Laporan', 'target' => 'rw-status', 'icon' => 'bi-list-check'],
        ['group' => 'Analitik laporan', 'label' => 'Tren & Distribusi', 'target' => 'rw-analitik', 'icon' => 'bi-graph-up-arrow'],
        ['group' => 'Sebaran wilayah', 'label' => 'Ringkasan per RT', 'target' => 'rw-ringkasan', 'icon' => 'bi-people'],
        ['group' => 'Kinerja RT', 'label' => 'Ranking RT', 'target' => 'rw-ranking', 'icon' => 'bi-trophy'],
    ];
@endphp

<x-dashboard.role-dashboard-styles />

<div class="dashboard-workspace rw-dashboard">
    <x-dashboard.topbar :home-url="route('rw.dashboard')" role-label="Dashboard RW" :context="$user->rw?->code ?? 'Wilayah RW'" :links="$navigation" />

    <div class="role-dashboard-shell">
        <x-dashboard.section-sidebar
            :items="$sectionNavigation"
            theme="blue"
            title="Dashboard Sections"
            footer-label="Periode Data"
            :footer-value="now()->locale('id')->isoFormat('D MMMM Y')"
        />

        <main id="main-content" class="role-dashboard-content">
            <header class="role-dashboard-heading">
                <div>
                    <h1>RW Dashboard</h1>
                    <p>Ringkasan laporan dan aktivitas wilayah {{ $user->rw?->code ?? 'RW' }}.</p>
                </div>
                <span class="role-date-chip"><i class="bi bi-calendar3" aria-hidden="true"></i>{{ now()->locale('id')->isoFormat('D MMMM Y') }}</span>
            </header>

            <section id="rw-statistik" class="role-section" aria-labelledby="rw-statistik-heading">
                <div class="role-section-titlebar">
                    <div><span class="eyebrow">Cakupan wilayah</span><h2 id="rw-statistik-heading">Statistik Utama</h2><p>Angka inti untuk membaca beban kerja RW secara cepat.</p></div>
                </div>
                <div class="role-kpi-grid">
                    <div class="role-kpi-card"><span class="role-kpi-icon bg-primary-subtle text-primary"><i class="bi bi-inbox"></i></span><div class="role-kpi-copy"><small>Total Laporan</small><strong>{{ number_format($total) }}</strong><span>Seluruh laporan lintas RT</span></div></div>
                    <div class="role-kpi-card"><span class="role-kpi-icon bg-info-subtle text-info-emphasis"><i class="bi bi-bell"></i></span><div class="role-kpi-copy"><small>Laporan Baru</small><strong>{{ number_format($newCount) }}</strong><span>Belum masuk penanganan</span></div></div>
                    <div class="role-kpi-card"><span class="role-kpi-icon bg-warning-subtle text-warning-emphasis"><i class="bi bi-hourglass-split"></i></span><div class="role-kpi-copy"><small>Sedang Diproses</small><strong>{{ number_format($processingCount) }}</strong><span>Dalam tindak lanjut petugas</span></div></div>
                    <div class="role-kpi-card"><span class="role-kpi-icon bg-success-subtle text-success"><i class="bi bi-check2-circle"></i></span><div class="role-kpi-copy"><small>Selesai</small><strong>{{ number_format($completedCount) }}</strong><span>Laporan telah dituntaskan</span></div></div>
                    <div class="role-kpi-card"><span class="role-kpi-icon bg-danger-subtle text-danger"><i class="bi bi-exclamation-triangle"></i></span><div class="role-kpi-copy"><small>Perlu Perhatian</small><strong>{{ number_format($attentionCount) }}</strong><span>Baru + diteruskan</span></div></div>
                </div>
            </section>

            <section id="rw-perhatian" class="role-section" aria-labelledby="rw-perhatian-heading">
                <div class="role-section-titlebar">
                    <div><span class="eyebrow">Perlu perhatian</span><h2 id="rw-perhatian-heading">Antrean Prioritas RW</h2><p>Temukan laporan yang membutuhkan koordinasi RW sebelum melihat statistik lanjutan.</p></div>
                    <a href="#antrean-laporan">Buka antrean lengkap</a>
                </div>
                <div class="attention-panel">
                    <div class="attention-metrics">
                        <div class="attention-stat"><i class="bi bi-bell"></i><div><strong>{{ number_format($newCount) }}</strong><span>Laporan baru</span></div></div>
                        <div class="attention-stat"><i class="bi bi-arrow-up-right-circle"></i><div><strong>{{ number_format($forwardedCount) }}</strong><span>Diteruskan dari RT</span></div></div>
                        <div class="attention-stat"><i class="bi bi-x-circle"></i><div><strong>{{ number_format($rejectedCount) }}</strong><span>Ditolak / perlu tinjau</span></div></div>
                    </div>
                    <a class="btn btn-sm btn-outline-danger" href="#antrean-laporan">Tinjau laporan</a>
                </div>
            </section>

            <section id="rw-eskalasi" class="role-section" aria-labelledby="rw-eskalasi-heading">
                <div class="role-section-titlebar">
                    <div><span class="eyebrow">Eskalasi</span><h2 id="rw-eskalasi-heading">Eskalasi dari RT</h2><p>Fokus pada laporan yang sudah dinaikkan oleh RT dan membutuhkan koordinasi tingkat RW.</p></div>
                </div>
                <div class="role-dashboard-two">
                    <article class="role-panel">
                        <div class="role-panel-header"><h3>Laporan Diteruskan</h3><span class="badge rounded-pill text-bg-primary">{{ number_format($forwardedCount) }}</span></div>
                        <div class="role-panel-body">
                            <p class="mb-3 text-secondary">Laporan yang diteruskan RT tetap terlihat dalam antrean RW untuk ditinjau, diterima, atau dilanjutkan sesuai kewenangan.</p>
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('rw.dashboard', ['status' => \App\Enums\ReportStatus::FORWARDED->value]) }}#antrean-laporan">Lihat laporan diteruskan</a>
                        </div>
                    </article>
                    <article class="role-panel">
                        <div class="role-panel-header"><h3>Cakupan Koordinasi</h3><span class="small text-secondary">Lintas RT</span></div>
                        <div class="role-panel-body">
                            <div class="attention-metrics">
                                <div class="attention-stat"><i class="bi bi-diagram-3"></i><div><strong>{{ number_format($activeRtCount) }}</strong><span>RT aktif</span></div></div>
                                <div class="attention-stat"><i class="bi bi-inbox"></i><div><strong>{{ number_format($total) }}</strong><span>Total laporan</span></div></div>
                                <div class="attention-stat"><i class="bi bi-hourglass-split"></i><div><strong>{{ number_format($processingCount) }}</strong><span>Sedang diproses</span></div></div>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <section id="rw-operasional" class="role-section" aria-labelledby="rw-operasional-heading">
                <div class="role-section-titlebar"><div><span class="eyebrow">Operasional</span><h2 id="rw-operasional-heading">Aksi Cepat RW</h2><p>Akses singkat ke pekerjaan yang paling sering dilakukan petugas RW.</p></div></div>
                <div class="role-panel">
                    <div class="role-panel-body">
                        <div class="quick-actions-row">
                            <a class="quick-action-chip" href="#antrean-laporan"><i class="bi bi-inbox text-danger"></i>Tinjau Laporan</a>
                            <a class="quick-action-chip" href="{{ route('rw.rts.index') }}"><i class="bi bi-diagram-3 text-primary"></i>Pantau Semua RT</a>
                            <a class="quick-action-chip" href="{{ route('rw.letters.index') }}"><i class="bi bi-envelope-check text-success"></i>Verifikasi Surat</a>
                            <a class="quick-action-chip" href="{{ route('rw.citizens.index') }}"><i class="bi bi-people text-info"></i>Data Warga</a>
                        </div>
                    </div>
                </div>
            </section>

            <section id="rw-terbaru" class="role-section" aria-labelledby="rw-terbaru-heading">
                <div class="role-section-titlebar"><div><span class="eyebrow">Lintas RT</span><h2 id="rw-terbaru-heading">Laporan Terbaru</h2><p>Lima laporan terbaru untuk membantu RW menangkap perubahan kondisi wilayah dengan cepat.</p></div><a href="#antrean-laporan">Lihat semua</a></div>
                <div class="role-panel">
                    <div class="compact-report-list">
                        @forelse ($latestReports as $report)
                            <a class="compact-report-item" href="{{ route('rw.reports.show', $report) }}">
                                <span class="compact-report-icon"><i class="bi bi-file-earmark-text"></i></span>
                                <span class="compact-report-copy"><strong>{{ $report->ticket_number }} · {{ $report->title }}</strong><span>{{ $report->rt?->code ?? 'RT —' }} · {{ $report->citizen?->name ?? 'Pelapor umum' }}</span></span>
                                <span class="compact-report-meta"><time>{{ $report->reported_at?->format('H:i') ?? '—' }}</time><span class="badge rounded-pill text-bg-{{ $report->status->bootstrapColor() }}">{{ $report->status->label() }}</span></span>
                            </a>
                        @empty
                            <div class="empty-compact-role">Belum ada laporan pada wilayah ini.</div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section id="rw-status" class="role-section" aria-labelledby="rw-status-heading">
                <div class="role-section-titlebar">
                    <div><span class="eyebrow">Progres penanganan</span><h2 id="rw-status-heading">Status Laporan</h2><p>Analitik status ditempatkan setelah pekerjaan utama agar dashboard tetap berorientasi tindakan.</p></div>
                </div>
                <div class="role-dashboard-two">
                    <article class="role-panel">
                        <div class="role-panel-header"><h3>Status Laporan di {{ $user->rw?->code ?? 'RW' }}</h3><span class="small text-secondary">{{ number_format($total) }} total</span></div>
                        <div class="role-panel-body status-donut-wrap">
                            <div class="status-chart"><canvas id="rwStatusDashboardChart" role="img" aria-label="Distribusi status laporan RW"></canvas></div>
                            <div class="status-legend">
                                @foreach ($statusCases as $status)
                                    @php($count = (int) ($totalsByStatus[$status->value] ?? 0))
                                    <div class="status-legend-row"><span class="status-legend-dot bg-{{ $status->bootstrapColor() }}"></span><span>{{ $status->label() }}</span><strong>{{ number_format($count) }}</strong></div>
                                @endforeach
                            </div>
                        </div>
                    </article>
                    <article class="role-panel">
                        <div class="role-panel-header"><h3>Sebaran Laporan per RT</h3><span class="small text-secondary">{{ $activeRtCount }} RT aktif</span></div>
                        <div class="role-panel-body">
                            <div class="distribution-list">
                                @forelse ($rts as $rt)
                                    @php($rtTotal = (int) ($totalsByRt[$rt->id] ?? 0))
                                    <div class="distribution-row">
                                        <strong>{{ $rt->code }}</strong>
                                        <div class="distribution-track"><div class="distribution-fill" style="width: {{ min(100, ($rtTotal / $maxRtReports) * 100) }}%"></div></div>
                                        <span class="text-end">{{ $rtTotal }}</span>
                                    </div>
                                @empty
                                    <div class="empty-compact-role">Belum ada RT di wilayah ini.</div>
                                @endforelse
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <section id="rw-analitik" class="role-section" aria-labelledby="rw-analitik-heading">
                <div class="role-section-titlebar"><div><span class="eyebrow">Analitik laporan</span><h2 id="rw-analitik-heading">Tren dan Distribusi</h2><p>Distribusi laporan antar-RT untuk membantu pembagian perhatian wilayah.</p></div></div>
                <div class="role-panel">
                    <div class="role-panel-body"><div class="analytics-chart-compact"><canvas id="rwDistributionDashboardChart" role="img" aria-label="Grafik distribusi laporan per RT"></canvas></div></div>
                </div>
            </section>

            <section id="rw-ringkasan" class="role-section" aria-labelledby="rw-ringkasan-heading">
                <div class="role-section-titlebar"><div><span class="eyebrow">Sebaran layanan</span><h2 id="rw-ringkasan-heading">Ringkasan Laporan per RT</h2><p>Ringkasan cepat untuk menemukan RT dengan beban laporan lebih tinggi.</p></div></div>
                <div class="role-panel">
                    <div class="table-responsive">
                        <table class="table compact-table align-middle mb-0">
                            <thead><tr><th class="ps-3">RT</th><th>Nama</th><th>Total Laporan</th><th>Status Wilayah</th><th class="text-end pe-3">Aksi</th></tr></thead>
                            <tbody>
                                @forelse ($rts as $rt)
                                    <tr><td class="ps-3 fw-semibold">{{ $rt->code }}</td><td>{{ $rt->name }}</td><td>{{ number_format((int) ($totalsByRt[$rt->id] ?? 0)) }}</td><td><span class="badge rounded-pill {{ $rt->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $rt->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td class="text-end pe-3"><a class="btn btn-sm btn-outline-primary" href="{{ route('rw.rts.edit', $rt) }}">Detail</a></td></tr>
                                @empty
                                    <tr><td colspan="5"><div class="empty-compact-role">Belum ada data RT.</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section id="rw-ranking" class="role-section" aria-labelledby="rw-ranking-heading">
                <div class="role-section-titlebar"><div><span class="eyebrow">Kinerja wilayah</span><h2 id="rw-ranking-heading">Ranking RT</h2><p>Urutan aktivitas berdasarkan laporan dan layanan administrasi yang tercatat.</p></div></div>
                <div class="role-panel">
                    <div class="rank-list">
                        @forelse ($rankings->take(8) as $index => $row)
                            <div class="rank-row"><span class="rank-number">{{ $index + 1 }}</span><span class="rank-copy"><strong>{{ $row['label'] }}</strong><span>{{ number_format($row['reports']) }} laporan · {{ number_format($row['citizens']) }} warga · {{ number_format($row['letters']) }} surat</span></span><span class="rank-score">{{ number_format($row['activity']) }} aktivitas</span></div>
                        @empty
                            <div class="empty-compact-role">Belum ada data ranking.</div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section id="antrean-laporan" class="role-section role-panel" aria-labelledby="rw-antrean-heading">
                <div class="role-panel-header"><div><h3 id="rw-antrean-heading">Antrean Laporan</h3><span class="small text-secondary">Menampilkan {{ $reports->firstItem() ?? 0 }}–{{ $reports->lastItem() ?? 0 }} dari {{ $reports->total() }} laporan.</span></div></div>
                <div class="role-filter-panel">
                    <form method="GET" action="{{ route('rw.dashboard') }}" class="row g-2 align-items-center">
                        <div class="col-md-2"><select name="rt_id" class="form-select" aria-label="Filter RT"><option value="">Semua RT</option>@foreach($rts as $rt)<option value="{{ $rt->id }}" @selected((int) request('rt_id') === $rt->id)>{{ $rt->code }}</option>@endforeach</select></div>
                        <div class="col-md-2"><select name="status" class="form-select" aria-label="Filter status"><option value="">Semua status</option>@foreach($statusCases as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
                        <div class="col-md"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari tiket, warga, atau judul"></div>
                        <div class="col-auto"><button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Cari</button></div>
                        @if($hasActiveFilters)<div class="col-auto"><a class="btn btn-outline-secondary" href="{{ route('rw.dashboard') }}#antrean-laporan">Reset</a></div>@endif
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table compact-table table-hover align-middle mb-0">
                        <thead><tr><th class="ps-3">Tiket</th><th>RT</th><th>Pelapor</th><th>Judul</th><th>Status</th><th>Waktu</th><th class="text-end pe-3">Aksi</th></tr></thead>
                        <tbody>
                            @forelse($reports as $report)
                                <tr><td class="ps-3 fw-semibold">{{ $report->ticket_number }}</td><td>{{ $report->rt?->code ?? '—' }}</td><td>{{ $report->citizen?->name ?? 'Pelapor umum' }}</td><td style="min-width: 15rem">{{ $report->title }}</td><td><span class="badge rounded-pill text-bg-{{ $report->status->bootstrapColor() }}">{{ $report->status->label() }}</span></td><td class="text-nowrap">{{ $report->reported_at?->format('d/m H:i') ?? '—' }}</td><td class="text-end pe-3"><a class="btn btn-sm btn-outline-primary" href="{{ route('rw.reports.show', $report) }}">Detail</a></td></tr>
                            @empty
                                <tr><td colspan="7"><div class="empty-compact-role">{{ $hasActiveFilters ? 'Tidak ada laporan yang cocok dengan filter.' : 'Belum ada laporan warga.' }}</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($reports->hasPages())<div class="border-top px-3 pt-3">{{ $reports->links('pagination::bootstrap-5') }}</div>@endif
            </section>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.Chart === 'undefined') return;

    const statusCanvas = document.getElementById('rwStatusDashboardChart');
    if (statusCanvas) {
        new window.Chart(statusCanvas, {
            type: 'doughnut',
            data: {
                labels: @json($statusCases->map(fn($status) => $status->label())->values()),
                datasets: [{
                    data: @json($statusCases->map(fn($status) => (int) ($totalsByStatus[$status->value] ?? 0))->values()),
                    backgroundColor: ['#2f7dd3','#f4b740','#7b61c9','#21a66b','#d85a63'],
                    borderColor: '#fff', borderWidth: 3,
                }],
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } },
        });
    }

    const distributionCanvas = document.getElementById('rwDistributionDashboardChart');
    if (distributionCanvas) {
        new window.Chart(distributionCanvas, {
            type: 'bar',
            data: {
                labels: @json($rts->pluck('code')->values()),
                datasets: [{ label: 'Laporan', data: @json($rts->map(fn($rt) => (int) ($totalsByRt[$rt->id] ?? 0))->values()), backgroundColor: 'rgba(47,125,211,.78)', borderRadius: 7 }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(23,35,59,.07)' } }, x: { grid: { display: false } } } },
        });
    }
});
</script>
@endpush
