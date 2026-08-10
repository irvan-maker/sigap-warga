<section class="analytics-section my-4" aria-labelledby="village-analytics-title">
    <div class="mb-3"><p class="section-eyebrow mb-1">Analitik pengambilan keputusan</p><h2 id="village-analytics-title" class="h3 section-title mb-1">Statistik Desa</h2><p class="text-secondary mb-0">Ringkasan menyeluruh berdasarkan data operasional desa.</p></div>

    <div class="row g-3 mb-4">
        @foreach([['citizens','people','Total Warga','gl-blue'],['family_cards','card-heading','Total KK','gl-purple'],['reports','clipboard-data','Total Laporan','gl-teal'],['letters','envelope-paper','Total Surat','gl-amber']] as [$key,$icon,$label,$accent])
            <div class="col-6 col-xl-3">
                <div class="card navigation-card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <span class="icon-box icon-box--{{ $accent }} d-inline-flex align-items-center justify-content-center mb-3">
                            <i class="bi bi-{{ $icon }}"></i>
                        </span>
                        <div class="text-secondary small mb-1 fw-semibold text-uppercase">{{ $label }}</div>
                        <div class="display-6 fw-bold">{{ number_format($analytics['kpis'][$key]) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card dashboard-panel insight-card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex gap-3">
                <span class="icon-box icon-box--gl-amber d-inline-flex align-items-center justify-content-center flex-shrink-0">
                    <i class="bi bi-lightbulb"></i>
                </span>
                <div>
                    <h3 class="h5 section-title">Insight Hari Ini</h3>
                    <ul class="mb-0 ps-3">
                        @foreach($analytics['insights'] as $insight)
                            <li class="mb-1">{{ $insight }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        @foreach([['monthlyReportsChart','Laporan per Bulan']] as [$id,$title])
            <div class="col-12"><section class="card dashboard-panel h-100 border-0 shadow-sm"><div class="card-body p-4"><h3 class="h5 section-title mb-3">{{ $title }}</h3><div class="analytics-chart"><canvas id="{{ $id }}" role="img" aria-label="Grafik {{ $title }}"></canvas></div></div></section></div>
        @endforeach
    </div>

    @php($mostActive = $analytics['rankings']->sortByDesc('activity')->first())
    @php($mostReports = $analytics['rankings']->sortByDesc('reports')->first())
    <div class="row g-3">
        <div class="col-md-6">
            <div class="card navigation-card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <p class="section-eyebrow mb-1">Aktivitas layanan</p>
                    <h3 class="h5">RT Paling Aktif</h3>
                    <div class="fs-4 fw-bold text-primary">{{ $mostActive['label'] ?? 'Belum ada data' }}</div>
                    <p class="text-secondary mb-0">{{ number_format($mostActive['activity'] ?? 0) }} aktivitas laporan dan surat.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card navigation-card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <p class="section-eyebrow mb-1">Laporan warga</p>
                    <h3 class="h5">RT dengan Laporan Terbanyak</h3>
                    <div class="fs-4 fw-bold text-primary">{{ $mostReports['label'] ?? 'Belum ada data' }}</div>
                    <p class="text-secondary mb-0">{{ number_format($mostReports['reports'] ?? 0) }} laporan tercatat.</p>
                </div>
            </div>
        </div>
    </div>
</section>

@push('styles')
    <style>
        /* Ikon chip kaca untuk kartu KPI & insight */
        .kelurahan-dashboard .icon-box {
            width: 2.75rem;
            height: 2.75rem;
            font-size: 1.2rem;
            border-radius: 14px;
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            box-shadow: 0 4px 14px rgba(31, 60, 136, 0.12);
        }
        .kelurahan-dashboard .icon-box--gl-blue {
            background: rgba(43, 92, 255, 0.14);
            border: 1px solid rgba(43, 92, 255, 0.3);
            color: #2b5cff;
        }
        .kelurahan-dashboard .icon-box--gl-purple {
            background: rgba(124, 92, 255, 0.14);
            border: 1px solid rgba(124, 92, 255, 0.3);
            color: #7c5cff;
        }
        .kelurahan-dashboard .icon-box--gl-teal {
            background: rgba(20, 184, 166, 0.14);
            border: 1px solid rgba(20, 184, 166, 0.3);
            color: #0f9c8d;
        }
        .kelurahan-dashboard .icon-box--gl-amber {
            background: rgba(255, 176, 32, 0.16);
            border: 1px solid rgba(255, 176, 32, 0.35);
            color: #c97e00;
        }

        /* Insight card dikasih aksen kiri lembut, senada tema kaca */
        .kelurahan-dashboard .insight-card {
            border-left: 3px solid rgba(255, 176, 32, 0.55) !important;
        }
        .kelurahan-dashboard .insight-card ul li {
            font-weight: 500;
        }

        /* Bingkai chart kaca, senada card lain */
        .kelurahan-dashboard .analytics-chart {
            border: 1px solid rgba(43, 92, 255, 0.15);
            border-radius: 14px;
            padding: 14px;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        .kelurahan-dashboard .analytics-chart canvas {
            max-height: 280px;
        }

        .kelurahan-dashboard .section-title {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            font-weight: 700;
        }
    </style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const inkColor = '#1c2340';
    const blue = '#2b5cff';
    const charts = [
        ['monthlyReportsChart', 'line', @json($analytics['monthly_reports']), 'Laporan'],
    ];
    charts.forEach(([id, type, series, label]) => new Chart(document.getElementById(id), {
        type,
        data: {
            labels: series.labels,
            datasets: [{
                label,
                data: series.data,
                borderColor: blue,
                backgroundColor: type === 'doughnut'
                    ? ['#2b5cff', '#7c5cff', '#14b8a6', '#ffb020', '#ff4d8d', '#6d90ff']
                    : 'rgba(43, 92, 255, 0.16)',
                fill: type === 'line',
                tension: 0.35,
                borderWidth: 3,
                pointStyle: 'circle',
                pointRadius: 4,
                pointBackgroundColor: '#fff',
                pointBorderColor: blue,
                pointBorderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: inkColor, font: { weight: '600' }, boxWidth: 16 }
                }
            },
            scales: type === 'doughnut' ? {} : {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0, color: inkColor, font: { weight: '500' } },
                    grid: { color: 'rgba(43, 92, 255, 0.08)' }
                },
                x: {
                    ticks: { color: inkColor, font: { weight: '500' } },
                    grid: { color: 'rgba(43, 92, 255, 0.04)' }
                }
            }
        }
    }));
});
</script>
@endpush