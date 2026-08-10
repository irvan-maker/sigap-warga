@extends('layouts.app')

@section('title', 'Dashboard RT - SIGAP WARGA')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ==========================================================
           SIGAP WARGA — RT Dashboard: Glassmorphism (blue accent)
           ========================================================== */
        .rt-dashboard {
            --gl-blue: #2b5cff;
            --gl-blue-dark: #1a3fd6;
            --gl-blue-light: #6d90ff;
            --gl-ink: #1c2340;
            --gl-muted: #5a6485;
            --gl-glass-bg: rgba(255, 255, 255, 0.55);
            --gl-glass-border: rgba(255, 255, 255, 0.65);
            --gl-glass-shadow: 0 8px 32px rgba(31, 60, 136, 0.14);

            position: relative;
            min-height: 100vh;
            overflow-x: hidden;
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            color: var(--gl-ink);
            background: linear-gradient(165deg, #eef2fc 0%, #e6ecfb 45%, #eef4fb 100%);
        }

        .rt-dashboard h1,
        .rt-dashboard h2,
        .rt-dashboard h3,
        .rt-dashboard .navbar-brand {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
        }

        /* floating blurred color blobs = the "glass" needs something to refract */
        .rt-dashboard .glass-blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            z-index: 0;
            pointer-events: none;
        }
        .rt-dashboard .glass-blob--1 {
            width: 420px; height: 420px;
            top: -12%; left: -10%;
            background: radial-gradient(circle, rgba(43,92,255,0.55), transparent 70%);
        }
        .rt-dashboard .glass-blob--2 {
            width: 380px; height: 380px;
            top: 18%; right: -14%;
            background: radial-gradient(circle, rgba(124,92,255,0.45), transparent 70%);
        }
        .rt-dashboard .glass-blob--3 {
            width: 460px; height: 460px;
            bottom: -16%; left: 22%;
            background: radial-gradient(circle, rgba(52,209,200,0.4), transparent 70%);
        }

        .rt-dashboard > .navbar { position: relative; z-index: 2; }
        .rt-dashboard > main { position: relative; z-index: 1; }

        /* ---------- Navbar ---------- */
        .rt-dashboard .navbar {
            background: rgba(255, 255, 255, 0.6) !important;
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.5) !important;
        }
        .rt-dashboard .brand-mark {
            width: 34px; height: 34px;
            font-size: .8rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--gl-blue), var(--gl-blue-light));
            box-shadow: 0 4px 14px rgba(43, 92, 255, 0.4);
        }
        .rt-dashboard .navbar-brand { color: var(--gl-blue) !important; }
        .rt-dashboard .navbar .btn-outline-danger {
            background: rgba(255, 59, 59, 0.08);
            border: 1px solid rgba(255, 59, 59, 0.35);
            color: #d13a3a;
            backdrop-filter: blur(6px);
            font-weight: 600;
        }
        .rt-dashboard .navbar .btn-outline-danger:hover {
            background: #ff3b3b;
            border-color: #ff3b3b;
            color: #fff;
        }

        /* ---------- Hero ---------- */
        .rt-dashboard .dashboard-hero {
            background: linear-gradient(135deg, rgba(43, 92, 255, 0.6) 0%, rgba(94, 74, 255, 0.5) 100%);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 20px 50px rgba(31, 60, 136, 0.22);
        }
        .rt-dashboard .dashboard-hero .badge {
            backdrop-filter: blur(6px);
        }
        .rt-dashboard .hero-meta {
            background: rgba(255, 255, 255, 0.16);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* ---------- Generic glass card treatment ---------- */
        .rt-dashboard .card {
            background: var(--gl-glass-bg) !important;
            backdrop-filter: blur(18px) saturate(180%);
            -webkit-backdrop-filter: blur(18px) saturate(180%);
            border: 1px solid var(--gl-glass-border) !important;
            border-radius: 20px !important;
            box-shadow: var(--gl-glass-shadow) !important;
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .rt-dashboard .card.navigation-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 40px rgba(31, 60, 136, 0.22) !important;
        }
        .rt-dashboard .card-header {
            background: transparent !important;
            border-bottom: 1px solid rgba(43, 92, 255, 0.12) !important;
        }

        .rt-dashboard .section-eyebrow {
            text-transform: uppercase;
            font-weight: 800;
            font-size: .72rem;
            letter-spacing: .09em;
            color: var(--gl-blue);
        }

        /* ---------- Insight card ---------- */
        .rt-dashboard .insight-card {
            border-left: 3px solid rgba(43, 92, 255, 0.5) !important;
        }
        .rt-dashboard .insight-card i.bi-lightbulb {
            color: var(--gl-blue) !important;
        }

        /* ---------- Metric cards ---------- */
        .rt-dashboard .metric-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px; height: 44px;
            border-radius: 14px;
            font-weight: 700;
            backdrop-filter: blur(6px);
        }
        .rt-dashboard .metric-card-total {
            background: linear-gradient(135deg, rgba(43, 92, 255, 0.16), rgba(124, 92, 255, 0.12)) !important;
            border: 1px solid rgba(43, 92, 255, 0.3) !important;
        }
        .rt-dashboard .metric-card-total .metric-icon {
            background: var(--gl-blue) !important;
            color: #fff !important;
        }
        .rt-dashboard .progress {
            height: 6px;
            background: rgba(43, 92, 255, 0.12);
            border-radius: 999px;
            overflow: hidden;
        }
        .rt-dashboard .progress-bar {
            border-radius: 999px;
        }

        /* ---------- Quick actions ---------- */
        .rt-dashboard .quick-action {
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.6) !important;
            border-radius: 16px !important;
            backdrop-filter: blur(10px);
            transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .rt-dashboard .quick-action:hover {
            transform: translateX(3px);
            background: rgba(255, 255, 255, 0.75);
            box-shadow: 0 10px 26px rgba(31, 60, 136, 0.16);
        }
        .rt-dashboard .quick-action-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px; height: 40px;
            border-radius: 12px;
            font-weight: 700;
            font-size: .8rem;
            backdrop-filter: blur(6px);
        }

        /* ---------- Activity feed ---------- */
        .rt-dashboard .activity-item { color: inherit; }
        .rt-dashboard .activity-item:hover strong { color: var(--gl-blue); }
        .rt-dashboard .activity-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            box-shadow: 0 0 0 4px rgba(43, 92, 255, 0.12);
        }
        .rt-dashboard .empty-icon {
            width: 48px; height: 48px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 50%;
            background: rgba(43, 92, 255, 0.12);
            color: var(--gl-blue);
            font-weight: 700;
        }

        /* ---------- Region info rows ---------- */
        .rt-dashboard .region-row.border-bottom {
            border-color: rgba(43, 92, 255, 0.14) !important;
        }

        /* ---------- Buttons ---------- */
        .rt-dashboard .btn {
            border-radius: 12px;
            font-weight: 700;
        }
        .rt-dashboard .btn-primary {
            background: linear-gradient(135deg, var(--gl-blue), var(--gl-blue-light));
            border: none;
            box-shadow: 0 8px 22px rgba(43, 92, 255, 0.35);
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .rt-dashboard .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(43, 92, 255, 0.45);
        }
        .rt-dashboard .btn-outline-primary {
            background: rgba(43, 92, 255, 0.08);
            border: 1px solid rgba(43, 92, 255, 0.4);
            color: var(--gl-blue);
            backdrop-filter: blur(6px);
        }
        .rt-dashboard .btn-outline-primary:hover {
            background: var(--gl-blue);
            border-color: var(--gl-blue);
            color: #fff;
        }
        .rt-dashboard .btn-outline-secondary {
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(28, 35, 64, 0.2);
            color: var(--gl-ink);
            backdrop-filter: blur(6px);
        }

        /* ---------- Forms ---------- */
        .rt-dashboard .form-select,
        .rt-dashboard .form-control {
            background: rgba(255, 255, 255, 0.6) !important;
            border: 1px solid rgba(43, 92, 255, 0.25) !important;
            border-radius: 12px !important;
            backdrop-filter: blur(6px);
        }
        .rt-dashboard .form-select:focus,
        .rt-dashboard .form-control:focus {
            border-color: var(--gl-blue) !important;
            box-shadow: 0 0 0 3px rgba(43, 92, 255, 0.15) !important;
            background: rgba(255, 255, 255, 0.85) !important;
        }

        /* ---------- Badges ---------- */
        .rt-dashboard .badge {
            font-weight: 700;
            backdrop-filter: blur(6px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        /* ---------- Table ---------- */
        .rt-dashboard .table-responsive {
            border-radius: 16px;
            overflow: hidden;
        }
        .rt-dashboard table.table thead.table-light th {
            background: rgba(43, 92, 255, 0.08) !important;
            color: var(--gl-blue);
            text-transform: uppercase;
            font-size: .72rem;
            letter-spacing: .06em;
            font-weight: 700;
            border-bottom: 1px solid rgba(43, 92, 255, 0.15);
        }
        .rt-dashboard table.table tbody tr {
            border-bottom: 1px solid rgba(43, 92, 255, 0.08);
        }
        .rt-dashboard .table-hover tbody tr:hover {
            background-color: rgba(43, 92, 255, 0.06);
        }

        /* ---------- Pagination ---------- */
        .rt-dashboard .pagination .page-link {
            border: 1px solid rgba(43, 92, 255, 0.25);
            border-radius: 10px;
            margin-inline: 3px;
            color: var(--gl-blue);
            font-weight: 600;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(6px);
        }
        .rt-dashboard .pagination .page-item.active .page-link {
            background: var(--gl-blue);
            border-color: var(--gl-blue);
            color: #fff;
        }
    </style>
@endpush

@section('content')
    @php
        $user = auth()->user();
        $hasActiveFilters = request()->filled('status') || request()->filled('search');
        $recentReports = $reports->take(4);
    @endphp

    <div class="rt-dashboard min-vh-100">
        <span class="glass-blob glass-blob--1" aria-hidden="true"></span>
        <span class="glass-blob glass-blob--2" aria-hidden="true"></span>
        <span class="glass-blob glass-blob--3" aria-hidden="true"></span>

        <nav class="navbar bg-white border-bottom sticky-top" aria-label="Navigasi utama">
            <div class="container py-1">
                <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-primary" href="{{ route('rt.dashboard') }}">
                    <span class="brand-mark d-inline-flex align-items-center justify-content-center rounded-3 text-white" aria-hidden="true">SW</span>
                    <span>SIGAP WARGA</span>
                </a>
                <div class="d-flex align-items-center gap-3">
                    <div class="d-none d-sm-block text-end lh-sm">
                        <span class="small fw-semibold d-block">{{ $user->name }}</span>
                        <span class="text-secondary small">Petugas {{ $user->rt?->code ?? 'RT' }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-outline-danger btn-sm px-3" type="submit">Keluar</button>
                    </form>
                </div>
            </div>
        </nav>

        <main class="container py-4 py-lg-5">
            <header class="dashboard-hero overflow-hidden position-relative rounded-4 p-4 p-lg-5 mb-4 text-white shadow-sm">
                <div class="position-relative d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-4"><div><span class="badge rounded-pill bg-white bg-opacity-10 border border-white border-opacity-25 px-3 py-2 mb-3">Dashboard RT</span><p class="text-white-50 mb-1">Selamat datang,</p><h1 class="display-6 fw-bold mb-3">{{ $user->name }}</h1><p class="mb-0 text-white-75">Pantau layanan warga dan laksanakan sensus keluarga dalam satu halaman.</p></div><div class="hero-meta rounded-4 p-3 p-lg-4"><div class="small text-white-50 text-uppercase fw-semibold mb-1">Wilayah tugas</div><div class="h5 fw-bold mb-2">{{ $user->rt?->code ?? 'RT belum tersedia' }} · {{ $user->rw?->code ?? 'RW belum tersedia' }}</div><div class="small text-white-75">{{ now()->timezone('Asia/Jakarta')->locale('id')->isoFormat('dddd, D MMMM Y') }}</div></div></div>
            </header>

            <div class="card insight-card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h3 class="h5"><i class="bi bi-lightbulb me-2 text-primary"></i>Insight Hari Ini</h3>
                    <ul class="mb-0">
                        @foreach($analytics['insights'] as $insight)
                            <li>{{ $insight }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <section class="card border-0 shadow-sm mb-4"><div class="card-body"><div class="d-flex flex-wrap justify-content-between align-items-center gap-3"><div><h2 class="h5 mb-1">Pengajuan Surat</h2><div class="d-flex flex-wrap gap-2 small"><span>Draft: {{ $letterCounts['DRAFT'] ?? 0 }}</span><span>Diajukan: {{ $letterCounts['SUBMITTED'] ?? 0 }}</span><span>Diproses: {{ ($letterCounts['RW_REVIEWED'] ?? 0)+($letterCounts['APPROVED'] ?? 0) }}</span><span>Selesai: {{ $letterCounts['ISSUED'] ?? 0 }}</span><span>Ditolak: {{ $letterCounts['REJECTED'] ?? 0 }}</span></div></div><a class="btn btn-primary" href="{{ route('rt.letters.index') }}">Buka Pengajuan Surat</a></div></div></section>
            <section class="mb-4" aria-labelledby="master-data-heading"><h2 id="master-data-heading" class="h4 fw-bold mb-3">Master Data</h2><div class="row g-3"><div class="col-lg-6"><a class="card navigation-card h-100 text-decoration-none shadow-sm" href="{{ route('rt.household-census.create') }}"><span class="card-body"><strong class="d-block">Sensus Warga</strong><span class="text-secondary">{{ number_format($activeCitizenCount) }} warga aktif</span></span></a></div><div class="col-lg-6">
    <div class="card h-100 shadow-sm">
        <div class="card-body">
            <strong class="d-block mb-2">Kelengkapan Data</strong>
            <a class="d-block text-decoration-none text-secondary py-1" href="{{ route('rt.citizens.index', ['has_family_card' => 0]) }}">
                <span class="small">{{ $citizensWithoutFamilyCardCount }} warga tanpa KK</span>
            </a>
            <a class="d-block text-decoration-none text-secondary py-1" href="{{ route('rt.citizens.index', ['has_nik' => 0]) }}">
                <span class="small">{{ $citizensWithoutNikCount }} warga tanpa NIK</span>
            </a>
            <a class="d-block text-decoration-none text-secondary py-1" href="{{ route('rt.family-cards.index', ['has_head' => 0]) }}">
                <span class="small">{{ $familyCardsWithoutHeadCount }} KK tanpa kepala keluarga</span>
            </a>
        </div>
    </div>
</div></section>
            <h2 class="h4 fw-bold mb-3">Laporan Warga</h2>
            <section class="mb-4" aria-labelledby="kpi-heading">
                <div class="d-flex justify-content-between align-items-end mb-3">
                    <div>
                        <p class="section-eyebrow mb-1">Ikhtisar</p>
                        <h2 id="kpi-heading" class="h4 fw-bold mb-0">Kinerja Laporan</h2>
                    </div>
                    <span class="text-secondary small d-none d-sm-inline">Diperbarui hari ini</span>
                </div>
                <div class="row g-3">
                    <div class="col-sm-6 col-xl">
                        <div class="card metric-card metric-card-total h-100 border-0 shadow-sm">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <div class="text-secondary small text-uppercase fw-semibold">Total Laporan</div>
                                        <div class="display-6 fw-bold mt-2 mb-1">{{ number_format($total) }}</div>
                                        <div class="text-secondary small">Seluruh laporan wilayah</div>
                                    </div>
                                    <span class="metric-icon bg-primary-subtle text-primary" aria-hidden="true">#</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @foreach (\App\Enums\ReportStatus::cases() as $status)
                        @php
                            $statusTotal = $totalsByStatus[$status->value];
                            $percentage = $total > 0 ? ($statusTotal / $total) * 100 : 0;
                        @endphp
                        <div class="col-sm-6 col-xl">
                            <div class="card metric-card h-100 border-0 shadow-sm">
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