@extends('layouts.app')

@section('title', 'Dashboard RT - SIGAP WARGA')

@section('content')
@php
    $user = auth()->user();
    $hasActiveFilters = request()->filled('status') || request()->filled('search');
    $statusCases = collect(\App\Enums\ReportStatus::cases());
    $newCount = (int) ($totalsByStatus[\App\Enums\ReportStatus::NEW->value] ?? 0);
    $processingCount = (int) ($totalsByStatus[\App\Enums\ReportStatus::PROCESSING->value] ?? 0);
    $forwardedCount = (int) ($totalsByStatus[\App\Enums\ReportStatus::FORWARDED->value] ?? 0);
    $completedCount = (int) ($totalsByStatus[\App\Enums\ReportStatus::COMPLETED->value] ?? 0);
    $rejectedCount = (int) ($totalsByStatus[\App\Enums\ReportStatus::REJECTED->value] ?? 0);
    $attentionCount = $newCount + $forwardedCount;
    $latestReports = $reports->getCollection()->take(5);
    $actionReports = $reports->getCollection()->filter(fn ($report) => in_array($report->status, [\App\Enums\ReportStatus::NEW, \App\Enums\ReportStatus::PROCESSING, \App\Enums\ReportStatus::FORWARDED], true))->take(5);

    $navigation = [
        ['label' => 'Dashboard', 'url' => route('rt.dashboard'), 'icon' => 'bi-grid-1x2', 'active' => true],
        ['label' => 'Laporan', 'url' => '#antrean-laporan', 'icon' => 'bi-inbox'],
        ['label' => 'Warga', 'url' => route('rt.citizens.index'), 'icon' => 'bi-people'],
        ['label' => 'Kartu Keluarga', 'url' => route('rt.family-cards.index'), 'icon' => 'bi-card-heading'],
        ['label' => 'Surat', 'url' => route('rt.letters.index'), 'icon' => 'bi-envelope-check'],
        ['label' => 'Sensus', 'url' => route('rt.household-census.create'), 'icon' => 'bi-clipboard-data'],
    ];

    $sectionNavigation = [
        ['group' => 'Ringkasan', 'label' => 'Statistik Utama', 'target' => 'rt-statistik', 'icon' => 'bi-bar-chart'],
        ['group' => 'Perlu tindakan', 'label' => 'Menunggu Verifikasi', 'target' => 'rt-tindakan', 'icon' => 'bi-patch-exclamation'],
        ['group' => 'Eskalasi', 'label' => 'Diteruskan ke RW', 'target' => 'rt-eskalasi', 'icon' => 'bi-arrow-up-right'],
        ['group' => 'Operasional', 'label' => 'Aksi Cepat RT', 'target' => 'rt-operasional', 'icon' => 'bi-lightning-charge'],
        ['group' => 'Aktivitas', 'label' => 'Laporan Terbaru', 'target' => 'rt-terbaru', 'icon' => 'bi-clock-history'],
        ['group' => 'Progres penanganan', 'label' => 'Status Laporan', 'target' => 'rt-status', 'icon' => 'bi-list-check'],
        ['group' => 'Data wilayah', 'label' => 'Kesiapan Data RT', 'target' => 'rt-kinerja', 'icon' => 'bi-speedometer2'],
    ];
@endphp

<x-dashboard.role-dashboard-styles />

<div class="dashboard-workspace rt-dashboard">
    <x-dashboard.topbar :home-url="route('rt.dashboard')" role-label="Dashboard RT" :context="$user->rt?->code ?? 'Wilayah RT'" :links="$navigation" />

    <div class="role-dashboard-shell">
        <x-dashboard.section-sidebar
            :items="$sectionNavigation"
            theme="green"
            title="Dashboard Sections"
            footer-label="Periode Data"
            :footer-value="now()->locale('id')->isoFormat('D MMMM Y')"
        />

        <main id="main-content" class="role-dashboard-content">
            <header class="role-dashboard-heading">
                <div>
                    <h1>RT Dashboard</h1>
                    <p>Ringkasan laporan dan aktivitas {{ $user->rt?->code ?? 'RT' }} · {{ $user->rw?->code ?? 'RW' }}.</p>
                </div>
                <span class="role-date-chip"><i class="bi bi-calendar3" aria-hidden="true"></i>{{ now()->locale('id')->isoFormat('D MMMM Y') }}</span>
            </header>

            <section id="rt-statistik" class="role-section" aria-labelledby="rt-statistik-heading">
                <div class="role-section-titlebar"><div><span class="eyebrow">Ringkasan</span><h2 id="rt-statistik-heading">Statistik Utama</h2><p>Angka inti untuk melihat antrean kerja RT tanpa membuka banyak halaman.</p></div></div>
                <div class="role-kpi-grid">
                    <div class="role-kpi-card"><span class="role-kpi-icon bg-success-subtle text-success"><i class="bi bi-inbox"></i></span><div class="role-kpi-copy"><small>Total Laporan</small><strong>{{ number_format($total) }}</strong><span>Seluruh laporan wilayah RT</span></div></div>
                    <div class="role-kpi-card"><span class="role-kpi-icon bg-warning-subtle text-warning-emphasis"><i class="bi bi-patch-exclamation"></i></span><div class="role-kpi-copy"><small>Menunggu Verifikasi</small><strong>{{ number_format($newCount) }}</strong><span>Perlu diperiksa petugas</span></div></div>
                    <div class="role-kpi-card"><span class="role-kpi-icon bg-primary-subtle text-primary"><i class="bi bi-clock-history"></i></span><div class="role-kpi-copy"><small>Sedang Diproses</small><strong>{{ number_format($processingCount) }}</strong><span>Dalam penanganan</span></div></div>
                    <div class="role-kpi-card"><span class="role-kpi-icon bg-success-subtle text-success"><i class="bi bi-check2-circle"></i></span><div class="role-kpi-copy"><small>Selesai</small><strong>{{ number_format($completedCount) }}</strong><span>Sudah dituntaskan</span></div></div>
                    <div class="role-kpi-card"><span class="role-kpi-icon bg-danger-subtle text-danger"><i class="bi bi-exclamation-triangle"></i></span><div class="role-kpi-copy"><small>Perlu Perhatian</small><strong>{{ number_format($attentionCount) }}</strong><span>Baru + diteruskan</span></div></div>
                </div>
            </section>

            <section id="rt-tindakan" class="role-section" aria-labelledby="rt-tindakan-heading">
                <div class="role-section-titlebar"><div><span class="eyebrow">Perlu tindakan</span><h2 id="rt-tindakan-heading">Menunggu Verifikasi</h2><p>Laporan aktif ditempatkan di atas agar petugas langsung melihat pekerjaan yang perlu ditangani.</p></div><a href="#antrean-laporan">Lihat semua</a></div>
                <div class="role-panel">
                    <div class="compact-report-list">
                        @forelse ($actionReports as $report)
                            <a class="compact-report-item" href="{{ route('rt.reports.show', $report) }}">
                                <span class="compact-report-icon"><i class="bi bi-exclamation-circle"></i></span>
                                <span class="compact-report-copy"><strong>{{ $report->ticket_number }} · {{ $report->title }}</strong><span>{{ $report->citizen?->name ?? 'Pelapor umum' }} · {{ $report->category?->label() ?? 'Lainnya' }}</span></span>
                                <span class="compact-report-meta"><time>{{ $report->reported_at?->format('H:i') ?? '—' }}</time><span class="badge rounded-pill text-bg-{{ $report->status->bootstrapColor() }}">{{ $report->status->label() }}</span></span>
                            </a>
                        @empty
                            <div class="empty-compact-role">Tidak ada laporan aktif yang perlu tindakan dari halaman saat ini.</div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section id="rt-eskalasi" class="role-section" aria-labelledby="rt-eskalasi-heading">
                <div class="role-section-titlebar"><div><span class="eyebrow">Eskalasi</span><h2 id="rt-eskalasi-heading">Eskalasi ke RW</h2><p>Pantau laporan yang perlu atau sudah diteruskan ketika penanganan melampaui kewenangan RT.</p></div></div>
                <div class="attention-panel">
                    <div class="attention-metrics">
                        <div class="attention-stat"><i class="bi bi-arrow-up-right-circle"></i><div><strong>{{ number_format($forwardedCount) }}</strong><span>Diteruskan ke RW</span></div></div>
                        <div class="attention-stat"><i class="bi bi-patch-exclamation"></i><div><strong>{{ number_format($newCount) }}</strong><span>Menunggu verifikasi</span></div></div>
                        <div class="attention-stat"><i class="bi bi-x-circle"></i><div><strong>{{ number_format($rejectedCount) }}</strong><span>Ditolak</span></div></div>
                    </div>
                    <a class="btn btn-sm btn-outline-danger" href="#antrean-laporan">Buka antrean</a>
                </div>
            </section>

            <section id="rt-operasional" class="role-section" aria-labelledby="rt-operasional-heading">
                <div class="role-section-titlebar"><div><span class="eyebrow">Operasional</span><h2 id="rt-operasional-heading">Aksi Cepat RT</h2><p>Aksi yang paling sering dipakai petugas ditempatkan sebelum bagian analitik.</p></div></div>
                <div class="role-panel">
                    <div class="role-panel-body">
                        <div class="quick-actions-row">
                            <a class="quick-action-chip" href="#antrean-laporan"><i class="bi bi-check2-square text-warning"></i>Verifikasi Laporan</a>
                            <a class="quick-action-chip" href="{{ route('rt.citizens.index') }}"><i class="bi bi-people text-success"></i>Data Warga</a>
                            <a class="quick-action-chip" href="{{ route('rt.letters.index') }}"><i class="bi bi-envelope-check text-primary"></i>Pengajuan Surat</a>
                            <a class="quick-action-chip" href="{{ route('rt.household-census.create') }}"><i class="bi bi-clipboard-data text-info"></i>Sensus Warga</a>
                        </div>
                    </div>
                </div>
            </section>

            <section id="rt-terbaru" class="role-section" aria-labelledby="rt-terbaru-heading">
                <div class="role-section-titlebar"><div><span class="eyebrow">Aktivitas</span><h2 id="rt-terbaru-heading">Laporan Terbaru</h2><p>Lima laporan terbaru untuk menjaga konteks aktivitas warga sebelum melihat ringkasan status.</p></div><a href="#antrean-laporan">Lihat semua</a></div>
                <div class="role-panel">
                    <div class="compact-report-list">
                        @forelse ($latestReports as $report)
                            <a class="compact-report-item" href="{{ route('rt.reports.show', $report) }}">
                                <span class="compact-report-icon"><i class="bi bi-file-earmark-text"></i></span>
                                <span class="compact-report-copy"><strong>{{ $report->ticket_number }} · {{ $report->title }}</strong><span>{{ $report->citizen?->name ?? 'Pelapor umum' }} · {{ $report->category?->label() ?? 'Lainnya' }}</span></span>
                                <span class="compact-report-meta"><time>{{ $report->reported_at?->format('d/m H:i') ?? '—' }}</time><span class="badge rounded-pill text-bg-{{ $report->status->bootstrapColor() }}">{{ $report->status->label() }}</span></span>
                            </a>
                        @empty
                            <div class="empty-compact-role">Belum ada laporan warga.</div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section id="rt-status" class="role-section" aria-labelledby="rt-status-heading">
                <div class="role-section-titlebar"><div><span class="eyebrow">Progres penanganan</span><h2 id="rt-status-heading">Status Laporan {{ $user->rt?->code ?? 'RT' }}</h2><p>Ringkasan status ditempatkan setelah antrean dan aksi agar tidak menggeser fokus operasional petugas.</p></div></div>
                <div class="role-panel">
                    <div class="role-panel-body status-donut-wrap">
                        <div class="status-chart"><canvas id="rtStatusDashboardChart" role="img" aria-label="Distribusi status laporan RT"></canvas></div>
                        <div class="status-legend">
                            @foreach ($statusCases as $status)
                                @php($count = (int) ($totalsByStatus[$status->value] ?? 0))
                                <div class="status-legend-row"><span class="status-legend-dot bg-{{ $status->bootstrapColor() }}"></span><span>{{ $status->label() }}</span><strong>{{ number_format($count) }}</strong></div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <section id="rt-kinerja" class="role-section" aria-labelledby="rt-kinerja-heading">
                <div class="role-section-titlebar"><div><span class="eyebrow">Data wilayah</span><h2 id="rt-kinerja-heading">Kesiapan Data RT</h2><p>Data administrasi tetap tersedia, tetapi ditempatkan setelah pekerjaan laporan selesai dibaca.</p></div></div>
                <div class="role-panel">
                    <div class="role-panel-body">
                        <div class="distribution-list">
                            @foreach ([
                                ['label' => 'Warga aktif', 'value' => $activeCitizenCount, 'max' => max(1, $activeCitizenCount), 'green' => true],
                                ['label' => 'KK aktif', 'value' => $activeFamilyCardCount, 'max' => max(1, $activeCitizenCount), 'green' => true],
                                ['label' => 'Warga tanpa NIK', 'value' => $citizensWithoutNikCount, 'max' => max(1, $activeCitizenCount), 'green' => false],
                                ['label' => 'Warga tanpa KK', 'value' => $citizensWithoutFamilyCardCount, 'max' => max(1, $activeCitizenCount), 'green' => false],
                                ['label' => 'KK tanpa kepala', 'value' => $familyCardsWithoutHeadCount, 'max' => max(1, $activeFamilyCardCount), 'green' => false],
                            ] as $row)
                                <div class="distribution-row" style="grid-template-columns: 110px minmax(0,1fr) 28px">
                                    <strong>{{ $row['label'] }}</strong>
                                    <div class="distribution-track"><div class="distribution-fill {{ $row['green'] ? 'green' : '' }}" style="width: {{ min(100, ($row['value'] / $row['max']) * 100) }}%"></div></div>
                                    <span class="text-end">{{ $row['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <section id="antrean-laporan" class="role-section role-panel" aria-labelledby="rt-antrean-heading">
                <div class="role-panel-header"><div><h3 id="rt-antrean-heading">Antrean Laporan</h3><span class="small text-secondary">Menampilkan {{ $reports->firstItem() ?? 0 }}–{{ $reports->lastItem() ?? 0 }} dari {{ $reports->total() }} laporan.</span></div></div>
                <div class="role-filter-panel">
                    <form method="GET" action="{{ route('rt.dashboard') }}" class="row g-2 align-items-center">
                        <div class="col-md-3"><select name="status" class="form-select" aria-label="Filter status"><option value="">Semua status</option>@foreach($statusCases as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
                        <div class="col-md"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari tiket, warga, atau judul"></div>
                        <div class="col-auto"><button class="btn btn-success" type="submit"><i class="bi bi-search me-1"></i>Cari</button></div>
                        @if($hasActiveFilters)<div class="col-auto"><a class="btn btn-outline-secondary" href="{{ route('rt.dashboard') }}#antrean-laporan">Reset</a></div>@endif
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table compact-table table-hover align-middle mb-0">
                        <thead><tr><th class="ps-3">Tiket</th><th>Pelapor</th><th>Judul</th><th>Kategori</th><th>Status</th><th>Waktu</th><th class="text-end pe-3">Aksi</th></tr></thead>
                        <tbody>
                            @forelse($reports as $report)
                                <tr><td class="ps-3 fw-semibold">{{ $report->ticket_number }}</td><td>{{ $report->citizen?->name ?? 'Pelapor umum' }}</td><td style="min-width: 15rem">{{ $report->title }}</td><td>{{ $report->category?->label() ?? 'Lainnya' }}</td><td><span class="badge rounded-pill text-bg-{{ $report->status->bootstrapColor() }}">{{ $report->status->label() }}</span></td><td class="text-nowrap">{{ $report->reported_at?->format('d/m H:i') ?? '—' }}</td><td class="text-end pe-3"><a class="btn btn-sm btn-outline-success" href="{{ route('rt.reports.show', $report) }}">Detail</a></td></tr>
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
    const canvas = document.getElementById('rtStatusDashboardChart');
    if (!canvas) return;

    new window.Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: @json($statusCases->map(fn($status) => $status->label())->values()),
            datasets: [{
                data: @json($statusCases->map(fn($status) => (int) ($totalsByStatus[$status->value] ?? 0))->values()),
                backgroundColor: ['#f4b740','#2f7dd3','#7b61c9','#21a66b','#d85a63'],
                borderColor: '#fff', borderWidth: 3,
            }],
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } },
    });
});
</script>
@endpush
