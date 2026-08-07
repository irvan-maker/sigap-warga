@php($mostActive = $analytics['rankings']->sortByDesc('activity')->first())
@php($mostReports = $analytics['rankings']->sortByDesc('reports')->first())

<section class="mt-5 mb-4" aria-labelledby="ranking-title">
    <div class="mb-3"><p class="section-eyebrow mb-1">Kinerja wilayah</p><h2 id="ranking-title" class="h4 section-title mb-0">Ranking RT</h2></div>
    <div class="row g-3">
        <div class="col-md-6"><div class="card h-100 border-0 shadow-sm"><div class="card-body p-4"><p class="section-eyebrow mb-1">Aktivitas layanan</p><h3 class="h5">RT Paling Aktif</h3><div class="fs-4 fw-bold text-primary">{{ $mostActive['label'] ?? 'Belum ada data' }}</div><p class="text-secondary mb-0">{{ number_format($mostActive['activity'] ?? 0) }} aktivitas laporan dan surat.</p></div></div></div>
        <div class="col-md-6"><div class="card h-100 border-0 shadow-sm"><div class="card-body p-4"><p class="section-eyebrow mb-1">Laporan warga</p><h3 class="h5">RT dengan Laporan Terbanyak</h3><div class="fs-4 fw-bold text-primary">{{ $mostReports['label'] ?? 'Belum ada data' }}</div><p class="text-secondary mb-0">{{ number_format($mostReports['reports'] ?? 0) }} laporan tercatat.</p></div></div></div>
    </div>
</section>

<section class="analytics-section mt-4 mb-5" aria-labelledby="village-analytics-title">
    <div class="mb-3"><p class="section-eyebrow mb-1">Analitik pengambilan keputusan</p><h2 id="village-analytics-title" class="h3 section-title mb-1">Statistik Desa</h2><p class="text-secondary mb-0">Ringkasan menyeluruh berdasarkan data operasional desa.</p></div>
    <div class="row g-3 mb-4">
        @foreach([['citizens','people','Total Warga'],['family_cards','card-heading','Total KK'],['reports','clipboard-data','Total Laporan'],['letters','envelope-paper','Total Surat']] as [$key,$icon,$label])
            <div class="col-6 col-xl-3"><div class="card h-100 border-0 shadow-sm"><div class="card-body p-4"><span class="analytics-icon-box mb-3"><i class="bi bi-{{ $icon }}"></i></span><div class="text-secondary small mb-1">{{ $label }}</div><div class="display-6 fw-bold">{{ number_format($analytics['kpis'][$key]) }}</div></div></div></div>
        @endforeach
    </div>
    <div class="card insight-card border-0 shadow-sm mb-4"><div class="card-body p-4"><div class="d-flex gap-3"><span class="analytics-icon-box analytics-icon-box-warning flex-shrink-0"><i class="bi bi-lightbulb"></i></span><div><h3 class="h5 section-title">Insight Hari Ini</h3><ul class="mb-0 ps-3">@foreach($analytics['insights'] as $insight)<li class="mb-1">{{ $insight }}</li>@endforeach</ul></div></div></div></div>

    <div class="row g-4 mb-4">
        @foreach([['monthlyReportsChart','Laporan per Bulan'],['monthlyLettersChart','Surat per Bulan'],['reportStatusChartAnalytics','Distribusi Status Laporan'],['reportsByRwChart','Laporan per RW']] as [$id,$title])
            <div class="col-12 col-lg-6"><section class="card h-100 border-0 shadow-sm"><div class="card-body p-4"><h3 class="h5 section-title mb-3">{{ $title }}</h3><div class="analytics-chart"><canvas id="{{ $id }}" role="img" aria-label="Grafik {{ $title }}"></canvas></div></div></section></div>
        @endforeach
    </div>

    <div class="text-center mb-4"><button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#completeVillageAnalytics" aria-expanded="false" aria-controls="completeVillageAnalytics"><i class="bi bi-bar-chart-line me-2" aria-hidden="true"></i>Lihat Analitik Lengkap</button></div>
    <div class="collapse" id="completeVillageAnalytics">
        <div class="row g-4">
            @foreach([['letterStatusChart','Distribusi Status Surat'],['lettersByRwChart','Surat per RW']] as [$id,$title])
                <div class="col-12 col-lg-6"><section class="card h-100 border-0 shadow-sm"><div class="card-body p-4"><h3 class="h5 section-title mb-3">{{ $title }}</h3><div class="analytics-chart"><canvas id="{{ $id }}" role="img" aria-label="Grafik {{ $title }}"></canvas></div></div></section></div>
            @endforeach
        </div>
    </div>
</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const charts = [
        ['monthlyReportsChart', 'line', @json($analytics['monthly_reports']), 'Laporan'],
        ['monthlyLettersChart', 'line', @json($analytics['monthly_letters']), 'Surat'],
        ['reportStatusChartAnalytics', 'doughnut', @json($analytics['report_statuses']), 'Laporan'],
        ['reportsByRwChart', 'bar', @json($analytics['reports_by_rw']), 'Laporan'],
        ['letterStatusChart', 'doughnut', @json($analytics['letter_statuses']), 'Surat'],
        ['lettersByRwChart', 'bar', @json($analytics['letters_by_rw']), 'Surat'],
    ].map(([id, type, series, label]) => new Chart(document.getElementById(id), {type, data: {labels: series.labels, datasets: [{label, data: series.data, borderColor: '#0b5cab', backgroundColor: type === 'doughnut' ? ['#0b5cab','#f4b740','#198754','#c92a3a','#6f42c1','#0dcaf0'] : 'rgba(11,92,171,.18)', fill: type === 'line', tension: .3}]}, options: {responsive: true, maintainAspectRatio: false, plugins: {legend: {position: 'bottom'}}, scales: type === 'doughnut' ? {} : {y: {beginAtZero: true, ticks: {precision: 0}}}}}));

    document.getElementById('completeVillageAnalytics')?.addEventListener('shown.bs.collapse', () => charts.slice(4).forEach((chart) => chart.resize()));
});
</script>
@endpush
