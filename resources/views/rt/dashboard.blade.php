@extends('layouts.app')

@section('title', 'Dashboard RT - SIGAP WARGA')

@section('content')
    @php
        $user = auth()->user();
        $hasActiveFilters = request()->filled('status') || request()->filled('search');
        $recentReports = $reports->take(4);
        $navigation = [
            ['label' => 'Dashboard', 'url' => route('rt.dashboard'), 'icon' => 'bi-grid-1x2', 'active' => true],
            ['label' => 'Laporan', 'url' => '#daftar-laporan', 'icon' => 'bi-inbox'],
            ['label' => 'Warga', 'url' => route('rt.citizens.index'), 'icon' => 'bi-people'],
            ['label' => 'Kartu Keluarga', 'url' => route('rt.family-cards.index'), 'icon' => 'bi-card-heading'],
            ['label' => 'Surat', 'url' => route('rt.letters.index'), 'icon' => 'bi-envelope-check'],
            ['label' => 'Sensus', 'url' => route('rt.household-census.create'), 'icon' => 'bi-clipboard-data'],
        ];
    @endphp

    <div class="dashboard-workspace rt-dashboard">
        <x-dashboard.topbar :home-url="route('rt.dashboard')" role-label="Dashboard RT" :context="$user->rt?->code ?? 'Wilayah RT'" :links="$navigation" />

        <main id="main-content" class="container dashboard-main">
            <x-dashboard.hero badge="Dashboard RT" title="Selamat datang, {{ $user->name }}" description="Tindak lanjuti laporan warga, jaga kualitas data, dan kelola layanan wilayah dari satu ruang kerja." icon="bi-geo-alt">
                <x-slot:meta><small class="d-block mb-1">Wilayah tugas</small><strong class="d-block h5 mb-1">{{ $user->rt?->code ?? 'RT belum tersedia' }} · {{ $user->rw?->code ?? 'RW belum tersedia' }}</strong><span class="small text-white-50">{{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}</span></x-slot:meta>
            </x-dashboard.hero>

            <section class="module-strip" aria-label="Status modul sistem">
                <div class="module-strip-copy"><span class="module-strip-icon"><i class="bi bi-boxes" aria-hidden="true"></i></span><div><strong class="d-block">Fokus layanan RT</strong><small class="text-secondary">Laporan cepat menjadi layanan utama dalam tahap pilot.</small></div></div>
                <div class="module-pills"><span class="module-pill module-pill-active">Laporan Cepat · PILOT</span><span class="module-pill module-pill-prototype">Sensus · PROTOTYPE</span><span class="module-pill module-pill-prototype">Posyandu · PROTOTYPE</span><span class="module-pill module-pill-prototype">Persuratan · PROTOTYPE</span></div>
            </section>

            <section class="dashboard-section" aria-labelledby="master-data-heading">
                <x-dashboard.section-heading eyebrow="Operasional wilayah" title="Data dan Layanan Warga" description="Akses data utama dan lihat bagian yang masih perlu dilengkapi." heading-id="master-data-heading" />
                <div class="row g-3">
                    <div class="col-lg-6"><div class="d-grid gap-3"><x-dashboard.action-card :href="route('rt.household-census.create')" title="Sensus Warga" description="{{ number_format($activeCitizenCount) }} warga aktif" icon="bi-clipboard-data" /><x-dashboard.action-card :href="route('rt.letters.index')" title="Pengajuan Surat" description="{{ number_format($letterCounts['SUBMITTED'] ?? 0) }} menunggu proses" icon="bi-envelope-check" tone="warning" />@if($hasPosyanduAssignment)<x-dashboard.action-card :href="route('posyandu.index')" title="Posyandu" description="{{ number_format($posyanduMonthlyVisitCount) }} kunjungan bulan ini" icon="bi-heart-pulse" tone="success" />@endif</div></div>
                    <div class="col-lg-6"><div class="card dashboard-panel-modern h-100"><div class="card-body p-3"><strong class="d-block mb-2">Kelengkapan Data</strong><div class="d-grid gap-2"><a class="completeness-link" href="{{ route('rt.citizens.index', ['completeness' => 'without_family_card']) }}"><span>Warga tanpa KK</span><span class="badge text-bg-light">{{ $citizensWithoutFamilyCardCount }}</span></a><a class="completeness-link" href="{{ route('rt.citizens.index', ['completeness' => 'without_nik']) }}"><span>Warga tanpa NIK</span><span class="badge text-bg-light">{{ $citizensWithoutNikCount }}</span></a><a class="completeness-link" href="{{ route('rt.family-cards.index', ['completeness' => 'without_head']) }}"><span>KK tanpa kepala keluarga</span><span class="badge text-bg-light">{{ $familyCardsWithoutHeadCount }}</span></a></div></div></div></div>
                </div>
            </section>

            <section class="dashboard-section" aria-labelledby="kpi-heading">
                <x-dashboard.section-heading eyebrow="Laporan warga" title="Kinerja Laporan" description="Status penanganan laporan yang masuk ke wilayah RT Anda." heading-id="kpi-heading" />
                <div class="row g-3">
                    <div class="col-sm-6 col-xl">
                        <x-dashboard.metric label="Total Laporan" :value="number_format($total)" helper="Seluruh laporan wilayah" icon="bi-inbox" tone="primary" href="#daftar-laporan" />
                    </div>
                    @foreach (\App\Enums\ReportStatus::cases() as $status)
                        @php
                            $statusTotal = $totalsByStatus[$status->value];
                            $percentage = $total > 0 ? ($statusTotal / $total) * 100 : 0;
                        @endphp
                        <div class="col-sm-6 col-xl">
                            <div class="card dashboard-panel-modern h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <div class="text-secondary small text-uppercase fw-semibold">{{ $status->label() }}</div>
                                            <div class="display-6 fw-bold mt-2 mb-1">{{ number_format($statusTotal) }}</div>
                                            <div class="text-secondary small">{{ number_format($percentage, 1) }}% dari total</div>
                                        </div>
                                        <span class="metric-icon bg-{{ $status->bootstrapColor() }}-subtle text-{{ $status->bootstrapColor() }}" aria-hidden="true">{{ $status->initial() }}</span>
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
                <div class="col-lg-4">
                    <section class="card dashboard-panel h-100 border-0 shadow-sm" aria-labelledby="region-heading">
                        <div class="card-body p-4">
                            <p class="section-eyebrow mb-1">Profil area</p>
                            <h2 id="region-heading" class="h5 fw-bold mb-4">Informasi Wilayah</h2>
                            <dl class="mb-0">
                                <div class="region-row d-flex justify-content-between gap-3 py-3 border-bottom">
                                    <dt class="text-secondary fw-normal">Nama RT</dt>
                                    <dd class="fw-semibold text-end mb-0">{{ $user->rt?->name ?? 'Belum tersedia' }}</dd>
                                </div>
                                <div class="region-row d-flex justify-content-between gap-3 py-3 border-bottom">
                                    <dt class="text-secondary fw-normal">Kode RT</dt>
                                    <dd class="fw-semibold text-end mb-0">{{ $user->rt?->code ?? '—' }}</dd>
                                </div>
                                <div class="region-row d-flex justify-content-between gap-3 py-3">
                                    <dt class="text-secondary fw-normal">Wilayah RW</dt>
                                    <dd class="fw-semibold text-end mb-0">{{ $user->rw?->name ?? $user->rw?->code ?? 'Belum tersedia' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </section>
                </div>

                <div class="col-lg-4">
                    <section class="card dashboard-panel h-100 border-0 shadow-sm" aria-labelledby="actions-heading">
                        <div class="card-body p-4">
                            <p class="section-eyebrow mb-1">Navigasi</p>
                            <h2 id="actions-heading" class="h5 fw-bold mb-4">Aksi Cepat</h2>
                            <div class="d-grid gap-3">
                                <a class="quick-action d-flex align-items-center gap-3 rounded-3 border p-3 text-decoration-none" href="#daftar-laporan">
                                    <span class="quick-action-icon bg-primary-subtle text-primary" aria-hidden="true">01</span>
                                    <span><strong class="d-block text-body">Kelola laporan</strong><small class="text-secondary">Tinjau laporan warga terbaru</small></span>
                                </a>
                                <a class="quick-action d-flex align-items-center gap-3 rounded-3 border p-3 text-decoration-none" href="{{ route('tracking.index') }}">
                                    <span class="quick-action-icon bg-warning-subtle text-warning-emphasis" aria-hidden="true">02</span>
                                    <span><strong class="d-block text-body">Lacak tiket</strong><small class="text-secondary">Periksa progres berdasarkan tiket</small></span>
                                </a>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="col-lg-4">
                    <section class="card dashboard-panel h-100 border-0 shadow-sm" aria-labelledby="activity-heading">
                        <div class="card-body p-4">
                            <p class="section-eyebrow mb-1">Pembaruan wilayah</p>
                            <h2 id="activity-heading" class="h5 fw-bold mb-4">Aktivitas Terbaru</h2>
                            @forelse ($recentReports as $report)
                                <a class="activity-item d-flex gap-3 text-decoration-none {{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}" href="{{ route('rt.reports.show', $report) }}">
                                    <span class="activity-dot bg-{{ $report->status->bootstrapColor() }} mt-2 flex-shrink-0" aria-hidden="true"></span>
                                    <span class="min-w-0">
                                        <strong class="d-block text-body text-truncate">{{ $report->title }}</strong>
                                        <small class="text-secondary d-block text-truncate">{{ $report->ticket_number }} · {{ $report->status->label() }}</small>
                                        <small class="text-secondary">{{ $report->reported_at?->locale('id')->diffForHumans() ?? 'Waktu belum tersedia' }}</small>
                                    </span>
                                </a>
                            @empty
                                <div class="empty-compact text-center py-4">
                                    <div class="empty-icon mx-auto mb-3" aria-hidden="true">✓</div>
                                    <h3 class="h6 fw-bold">Belum ada aktivitas</h3>
                                    <p class="small text-secondary mb-0">Aktivitas laporan terbaru akan ditampilkan di panel ini.</p>
                                </div>
                            @endforelse
                        </div>
                    </section>
                </div>
            </div>

            <section id="daftar-laporan" class="card dashboard-panel border-0 shadow-sm" aria-labelledby="reports-heading">
                <div class="card-header bg-white border-0 p-4 p-lg-5 pb-lg-3">
                    <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
                        <div>
                            <p class="section-eyebrow mb-1">Data operasional</p>
                            <h2 id="reports-heading" class="h4 fw-bold mb-1">Daftar Laporan</h2>
                            <p class="text-secondary small mb-0">Menampilkan {{ $reports->firstItem() ?? 0 }}–{{ $reports->lastItem() ?? 0 }} dari {{ $reports->total() }} laporan.</p>
                        </div>
                        <form method="GET" action="{{ route('rt.dashboard') }}" class="row g-2 align-items-end">
                            <div class="col-12 col-sm-auto">
                                <label for="status" class="form-label small fw-semibold">Status</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="">Semua status</option>
                                    @foreach (\App\Enums\ReportStatus::cases() as $status)
                                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-sm">
                                <label for="search" class="form-label small fw-semibold">Pencarian</label>
                                <input id="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Tiket, warga, atau judul">
                            </div>
                            <div class="col-auto"><button class="btn btn-primary" type="submit">Terapkan</button></div>
                            @if ($hasActiveFilters)
                                <div class="col-auto"><a class="btn btn-outline-secondary" href="{{ route('rt.dashboard') }}">Reset</a></div>
                            @endif
                        </form>
                    </div>
                </div>

                <div class="card-body p-0 pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th class="ps-4 ps-lg-5">Tiket</th><th>Warga</th><th>Judul</th><th>Status</th><th>Tanggal</th><th class="pe-4 pe-lg-5 text-end">Aksi</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($reports as $report)
                                    <tr>
                                        <td class="ps-4 ps-lg-5 fw-semibold text-nowrap">{{ $report->ticket_number }}</td>
                                        <td>{{ $report->citizen?->name ?? 'Data warga tidak tersedia' }}</td>
                                        <td class="text-break" style="min-width: 14rem">{{ $report->title }}</td>
                                        <td><span class="badge rounded-pill text-bg-{{ $report->status->bootstrapColor() }} px-3 py-2">{{ $report->status->label() }}</span></td>
                                        <td class="text-nowrap">{{ $report->reported_at?->format('d M Y, H:i') ?? '—' }}</td>
                                        <td class="pe-4 pe-lg-5 text-end"><a class="btn btn-outline-primary btn-sm text-nowrap" href="{{ route('rt.reports.show', $report) }}">Lihat detail</a></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center px-4 py-5">
                                            <div class="empty-table mx-auto py-3">
                                                <div class="empty-icon mx-auto mb-3" aria-hidden="true">{{ $hasActiveFilters ? '?' : '✓' }}</div>
                                                <h3 class="h5 fw-bold">{{ $hasActiveFilters ? 'Laporan tidak ditemukan' : 'Belum ada laporan warga' }}</h3>
                                                <p class="text-secondary {{ $hasActiveFilters ? 'mb-3' : 'mb-0' }}">{{ $hasActiveFilters ? 'Tidak ada data yang cocok. Coba gunakan kata kunci lain atau pilih status berbeda.' : 'Belum ada laporan warga yang perlu ditindaklanjuti. Laporan baru akan tersedia di sini setelah dicatat oleh Administrator.' }}</p>
                                                @if ($hasActiveFilters)
                                                    <a class="btn btn-outline-primary btn-sm" href="{{ route('rt.dashboard') }}">Hapus semua filter</a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($reports->hasPages())
                        <div class="border-top px-4 px-lg-5 pt-3">
                            {{ $reports->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>
            </section>
            @include('analytics.rt')
        </main>
    </div>
@endsection
