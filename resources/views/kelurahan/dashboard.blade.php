@extends('layouts.app')

@section('title', 'Dashboard Kelurahan - SIGAP WARGA')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ==========================================================
           SIGAP WARGA — Kelurahan Dashboard: Glassmorphism (blue accent)
           Sama persis dengan sistem desain Dashboard RT
           ========================================================== */
        .kelurahan-dashboard {
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

        .kelurahan-dashboard h1,
        .kelurahan-dashboard h2,
        .kelurahan-dashboard .navbar-brand {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
        }

        /* floating blurred color blobs = the "glass" needs something to refract */
        .kelurahan-dashboard .glass-blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            z-index: 0;
            pointer-events: none;
        }
        .kelurahan-dashboard .glass-blob--1 {
            width: 420px; height: 420px;
            top: -12%; left: -10%;
            background: radial-gradient(circle, rgba(43,92,255,0.55), transparent 70%);
        }
        .kelurahan-dashboard .glass-blob--2 {
            width: 380px; height: 380px;
            top: 18%; right: -14%;
            background: radial-gradient(circle, rgba(124,92,255,0.45), transparent 70%);
        }
        .kelurahan-dashboard .glass-blob--3 {
            width: 460px; height: 460px;
            bottom: -16%; left: 22%;
            background: radial-gradient(circle, rgba(52,209,200,0.4), transparent 70%);
        }

        .kelurahan-dashboard > .navbar { position: relative; z-index: 2; }
        .kelurahan-dashboard > main { position: relative; z-index: 1; }

        /* ---------- Navbar ---------- */
        .kelurahan-dashboard .navbar {
            background: rgba(255, 255, 255, 0.6) !important;
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.5) !important;
        }
        .kelurahan-dashboard .navbar-brand {
            font-weight: 800;
            letter-spacing: 0.01em;
            color: var(--gl-blue) !important;
        }
        .kelurahan-dashboard .navbar .btn-outline-danger {
            background: rgba(255, 59, 59, 0.08);
            border: 1px solid rgba(255, 59, 59, 0.35);
            color: #d13a3a;
            backdrop-filter: blur(6px);
            font-weight: 600;
            border-radius: 12px;
        }
        .kelurahan-dashboard .navbar .btn-outline-danger:hover {
            background: #ff3b3b;
            border-color: #ff3b3b;
            color: #fff;
        }

        /* ---------- Breadcrumb ---------- */
        .kelurahan-dashboard .breadcrumb-item {
            font-weight: 600;
            color: var(--gl-muted);
        }

        /* ---------- Hero ---------- */
        .kelurahan-hero {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(43, 92, 255, 0.6) 0%, rgba(94, 74, 255, 0.5) 100%) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 24px !important;
            box-shadow: 0 20px 50px rgba(31, 60, 136, 0.22);
        }
        .kelurahan-hero > .container {
            position: relative;
            z-index: 1;
        }
        .kelurahan-hero .badge {
            background: rgba(255, 255, 255, 0.16) !important;
            color: #fff !important;
            border: 1px solid rgba(255, 255, 255, 0.35) !important;
            backdrop-filter: blur(6px);
            font-weight: 700;
        }
        .kelurahan-hero .text-white-50 { color: rgba(255,255,255,0.8) !important; }
        .kelurahan-hero .text-white-75 { color: rgba(255,255,255,0.95) !important; }
        .hero-meta {
            background: rgba(255, 255, 255, 0.16);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 18px;
        }

        /* ---------- Section labels ---------- */
        .kelurahan-dashboard .section-eyebrow {
            text-transform: uppercase;
            font-weight: 800;
            font-size: .72rem;
            letter-spacing: .09em;
            color: var(--gl-blue);
        }

        /* ---------- Generic glass card treatment ---------- */
        .kelurahan-dashboard .card {
            background: var(--gl-glass-bg) !important;
            backdrop-filter: blur(18px) saturate(180%);
            -webkit-backdrop-filter: blur(18px) saturate(180%);
            border: 1px solid var(--gl-glass-border) !important;
            border-radius: 20px !important;
            box-shadow: var(--gl-glass-shadow) !important;
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .kelurahan-dashboard .navigation-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 40px rgba(31, 60, 136, 0.22) !important;
        }
        .kelurahan-dashboard .navigation-card .fs-3,
        .kelurahan-dashboard .navigation-card .fs-4 {
            font-weight: 700;
        }

        /* ---------- Info tiles / attention list ---------- */
        .kelurahan-dashboard .information-tile {
            background: rgba(43, 92, 255, 0.06);
            border: 1px solid rgba(43, 92, 255, 0.15);
            border-radius: 14px;
        }
        .kelurahan-dashboard .attention-link {
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 14px;
            backdrop-filter: blur(10px);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .kelurahan-dashboard .attention-link:hover {
            background: rgba(255, 255, 255, 0.8);
            transform: translateX(3px);
            box-shadow: 0 10px 26px rgba(31, 60, 136, 0.16);
        }

        /* ---------- Buttons ---------- */
        .kelurahan-dashboard .btn {
            border-radius: 12px;
            font-weight: 700;
        }
        .kelurahan-dashboard .btn-primary {
            background: linear-gradient(135deg, var(--gl-blue), var(--gl-blue-light));
            border: none;
            box-shadow: 0 8px 22px rgba(43, 92, 255, 0.35);
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .kelurahan-dashboard .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(43, 92, 255, 0.45);
        }
        .kelurahan-dashboard .btn-outline-primary {
            background: rgba(43, 92, 255, 0.08);
            border: 1px solid rgba(43, 92, 255, 0.4);
            color: var(--gl-blue);
            backdrop-filter: blur(6px);
        }
        .kelurahan-dashboard .btn-outline-primary:hover {
            background: var(--gl-blue);
            border-color: var(--gl-blue);
            color: #fff;
        }

        /* ---------- Badges (status chips) ---------- */
        .kelurahan-dashboard .badge {
            font-weight: 700;
            backdrop-filter: blur(6px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        /* ---------- Forms ---------- */
        .kelurahan-dashboard .form-select,
        .kelurahan-dashboard .form-control {
            background: rgba(255, 255, 255, 0.6) !important;
            border: 1px solid rgba(43, 92, 255, 0.25) !important;
            border-radius: 12px !important;
            backdrop-filter: blur(6px);
            font-weight: 500;
        }
        .kelurahan-dashboard .form-select:focus,
        .kelurahan-dashboard .form-control:focus {
            border-color: var(--gl-blue) !important;
            box-shadow: 0 0 0 3px rgba(43, 92, 255, 0.15) !important;
            background: rgba(255, 255, 255, 0.85) !important;
        }

        /* ---------- Tables ---------- */
        .kelurahan-dashboard .table-responsive {
            border-radius: 16px;
            overflow: hidden;
        }
        .kelurahan-dashboard .table {
            margin-bottom: 0;
        }
        .kelurahan-dashboard .table thead th {
            background: rgba(43, 92, 255, 0.08);
            color: var(--gl-blue);
            text-transform: uppercase;
            font-size: .72rem;
            letter-spacing: .06em;
            font-weight: 700;
            border-bottom: 1px solid rgba(43, 92, 255, 0.15);
            padding: .9rem .85rem;
        }
        .kelurahan-dashboard .table tbody td {
            border-bottom: 1px solid rgba(43, 92, 255, 0.08);
            padding: .8rem .85rem;
            font-weight: 500;
        }
        .kelurahan-dashboard .table-hover tbody tr:hover {
            background-color: rgba(43, 92, 255, 0.06);
        }
        .kelurahan-dashboard .interactive-row {
            cursor: pointer;
        }

        /* ---------- Pagination ---------- */
        .kelurahan-dashboard .pagination .page-link {
            border: 1px solid rgba(43, 92, 255, 0.25);
            border-radius: 10px;
            margin-inline: 3px;
            font-weight: 600;
            color: var(--gl-blue);
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(6px);
        }
        .kelurahan-dashboard .pagination .page-item.active .page-link {
            background: var(--gl-blue);
            border-color: var(--gl-blue);
            color: #fff;
        }
        .kelurahan-dashboard .pagination .page-item.disabled .page-link {
            box-shadow: none;
            opacity: .5;
        }

        /* ==========================================================
           Mobile (≤767px): tabel jadi kartu bertumpuk, bukan scroll
           horizontal yang kepotong; badge & form dirapikan.
           ========================================================== */
        @media (max-width: 767.98px) {
            .kelurahan-dashboard .kelurahan-hero,
            .kelurahan-dashboard main.container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            .kelurahan-dashboard .card-body {
                padding: 1rem !important;
            }

            /* badge row lebih rapat & tidak makan tempat */
            .kelurahan-dashboard .badge {
                font-size: .7rem;
                padding: .4em .6em !important;
            }

            /* tabel -> kartu bertumpuk */
            .kelurahan-dashboard .table-responsive {
                overflow-x: visible;
                border: none;
                box-shadow: none;
                background: transparent;
            }
            .kelurahan-dashboard .table,
            .kelurahan-dashboard .table thead,
            .kelurahan-dashboard .table tbody,
            .kelurahan-dashboard .table tr,
            .kelurahan-dashboard .table td {
                display: block;
                width: 100%;
            }
            .kelurahan-dashboard .table thead {
                display: none;
            }
            .kelurahan-dashboard .table tr {
                background: rgba(255, 255, 255, 0.55);
                backdrop-filter: blur(14px) saturate(180%);
                -webkit-backdrop-filter: blur(14px) saturate(180%);
                border: 1px solid rgba(255, 255, 255, 0.65);
                border-radius: 16px;
                box-shadow: 0 6px 20px rgba(31, 60, 136, 0.1);
                padding: .85rem 1rem;
                margin-bottom: .75rem;
            }
            .kelurahan-dashboard .table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: .75rem;
                padding: .5rem 0 !important;
                border-bottom: 1px solid rgba(43, 92, 255, 0.1) !important;
                text-align: right;
                white-space: normal !important;
                word-break: break-word;
            }
            .kelurahan-dashboard .table td:last-child {
                border-bottom: none !important;
            }
            .kelurahan-dashboard .table td::before {
                content: attr(data-label);
                font-weight: 700;
                font-size: .68rem;
                text-transform: uppercase;
                letter-spacing: .04em;
                color: var(--gl-blue);
                text-align: left;
                flex-shrink: 0;
            }
            .kelurahan-dashboard .table td[data-label="Aksi"] {
                justify-content: flex-end;
            }
            .kelurahan-dashboard .table td[data-label="Aksi"]::before {
                display: none;
            }
            .kelurahan-dashboard .table tbody tr[colspan],
            .kelurahan-dashboard .table tr:has(td[colspan]) {
                background: transparent;
                border: none;
                box-shadow: none;
                padding: 0;
            }
            .kelurahan-dashboard .table tr:has(td[colspan]) td {
                display: block;
                text-align: center;
            }

            /* filter form select/input full width, tidak kepotong */
            .kelurahan-dashboard form .form-select,
            .kelurahan-dashboard form .form-control {
                font-size: .9rem;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $officer = auth()->user();
        $reportDetailRoute = $officer->isSystemAdmin() ? 'reports.show' : 'kelurahan.reports.show';
        $canManageRws = $officer->isSystemAdmin() || $officer->isVillageSecretary();
    @endphp

    <div class="kelurahan-dashboard min-vh-100">
        <span class="glass-blob glass-blob--1" aria-hidden="true"></span>
        <span class="glass-blob glass-blob--2" aria-hidden="true"></span>
        <span class="glass-blob glass-blob--3" aria-hidden="true"></span>

        <nav class="navbar bg-white border-bottom sticky-top" aria-label="Navigasi utama">
            <div class="container py-1">
                <a class="navbar-brand fw-bold text-primary" href="{{ route('kelurahan.dashboard') }}">SIGAP WARGA</a>
                <div class="d-flex align-items-center gap-3">
                    <div class="d-none d-sm-block text-end lh-sm">
                        <span class="small fw-semibold d-block">{{ $officer->name }}</span>
                        <span class="text-secondary small">{{ $officer->position?->label() }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-outline-danger btn-sm" type="submit">Keluar</button>
                    </form>
                </div>
            </div>
        </nav>

        <main class="container py-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-4">
                    <li class="breadcrumb-item active" aria-current="page">Dashboard Kelurahan</li>
                </ol>
            </nav>

            <header class="kelurahan-hero rounded-4 p-4 p-lg-5 mb-4 text-white shadow-sm">
                <div class="container d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-4">
                    <div>
                        <span class="badge rounded-pill bg-white bg-opacity-10 border border-white border-opacity-25 px-3 py-2 mb-3">Layanan aktif</span>
                        <p class="text-white-50 mb-1">{{ config('village.name') }}</p>
                        <h1 class="h2 fw-bold mb-2">Selamat datang, {{ $officer->name }}</h1>
                        <p class="mb-0 text-white-75">{{ $officer->position?->label() }} · Pusat pemantauan laporan warga dan operasional wilayah desa.</p>
                    </div>
                    <div class="hero-meta rounded-3 p-3">
                        <span class="small text-white-50 d-block">Hari ini</span>
                        <strong>{{ now()->timezone('Asia/Jakarta')->locale('id')->isoFormat('dddd, D MMMM Y') }}</strong>
                    </div>
                </div>
            </header>

            @include('analytics.village')

            <section class="mb-4" aria-labelledby="kpi-heading">
                <div class="mb-3">
                    <p class="section-eyebrow mb-1">Layanan warga</p>
                    <h2 id="kpi-heading" class="h4 fw-bold mb-0">Ringkasan Laporan</h2>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-6 col-xl-3">
                        <a class="card navigation-card h-100 border-0 shadow-sm text-decoration-none" href="{{ route('kelurahan.letters.index') }}">
                            <span class="card-body p-4 d-flex flex-column">
                                <span class="text-secondary small d-block mb-1">Administrasi Surat</span>
                                <strong class="fs-3 text-body">{{ number_format($letterCount) }}</strong>
                                <small class="text-secondary mt-auto pt-2">pengajuan tercatat</small>
                            </span>
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <a class="card navigation-card h-100 border-0 shadow-sm text-decoration-none" href="{{ route('kelurahan.citizens.index') }}">
                            <span class="card-body p-4 d-flex flex-column">
                                <span class="text-secondary small d-block mb-1">Warga Aktif</span>
                                <strong class="fs-3 text-body">{{ number_format($activeCitizenCount) }}</strong>
                                <small class="text-secondary mt-auto pt-2">Lihat data warga</small>
                            </span>
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <a class="card navigation-card h-100 border-0 shadow-sm text-decoration-none" href="{{ route('kelurahan.family-cards.index') }}">
                            <span class="card-body p-4 d-flex flex-column">
                                <span class="text-secondary small d-block mb-1">KK Aktif</span>
                                <strong class="fs-3 text-body">{{ number_format($activeFamilyCardCount) }}</strong>
                                <small class="text-secondary mt-auto pt-2">Lihat data KK</small>
                            </span>
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <a class="card navigation-card h-100 border-0 shadow-sm text-decoration-none" href="{{ route('kelurahan.reports.index') }}#laporan">
                            <span class="card-body p-4 d-flex flex-column">
                                <span class="text-secondary small d-block mb-1">Total Laporan</span>
                                <strong class="fs-3 text-body">{{ number_format($total) }}</strong>
                                <small class="text-primary mt-auto pt-2">Lihat seluruh laporan</small>
                            </span>
                        </a>
                    </div>
                </div>

                <div class="row g-3">
                    @foreach (\App\Enums\ReportStatus::cases() as $status)
                        <div class="col-6 col-md-3">
                            <a class="card navigation-card h-100 border-0 shadow-sm text-decoration-none" href="{{ route('kelurahan.reports.index', ['status' => $status->value]) }}#laporan">
                                <span class="card-body p-3 d-flex flex-column">
                                    <span class="text-secondary small d-block mb-1">{{ $status->label() }}</span>
                                    <strong class="fs-4 text-body">{{ number_format($totalsByStatus[$status->value]) }}</strong>
                                    <small class="text-{{ $status->bootstrapColor() }} mt-auto pt-2">Lihat daftar</small>
                                </span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="mb-4" aria-labelledby="actions-heading">
                <div class="mb-3">
                    <p class="section-eyebrow mb-1">Operasional</p>
                    <h2 id="actions-heading" class="h4 fw-bold mb-0">Aksi Cepat</h2>
                </div>
                <div class="row g-3">
                    <div class="col-sm-6 col-xl-4">
                        <a class="card navigation-card h-100 border-0 shadow-sm text-decoration-none" href="{{ route('admin.reports.index') }}">
                            <span class="card-body p-4"><strong class="text-body d-block mb-1">Kelola Laporan</strong><small class="text-secondary">{{ $officer->isVillageHead() ? 'Pantau seluruh laporan warga' : 'Cari dan tindak lanjuti laporan warga' }}</small></span>
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-4">
                        <a class="card navigation-card h-100 border-0 shadow-sm text-decoration-none" href="{{ route('kelurahan.rws.index') }}">
                            <span class="card-body p-4"><strong class="text-body d-block mb-1">{{ $officer->isVillageHead() ? 'Lihat RW' : 'Kelola RW' }}</strong><small class="text-secondary">{{ $officer->isVillageHead() ? 'Lihat struktur wilayah RW' : 'Atur data dan status wilayah RW' }}</small></span>
                        </a>
                    </div>
                    @if ($officer->isSystemAdmin() || $officer->isVillageSecretary())
                        <div class="col-sm-6 col-xl-4">
                            <a class="card navigation-card h-100 border-0 shadow-sm text-decoration-none" href="{{ route('admin.users.index') }}">
                                <span class="card-body p-4"><strong class="text-body d-block mb-1">Kelola Akun Petugas</strong><small class="text-secondary">Atur akun dan penempatan petugas</small></span>
                            </a>
                        </div>
                    @endif
                </div>
            </section>

            <div class="row g-4 mb-4">
                <div class="col-xl-6">
                    <section class="card dashboard-panel h-100 border-0 shadow-sm" aria-labelledby="today-heading">
                        <div class="card-body p-4">
                            <p class="section-eyebrow mb-1">Hari ini</p>
                            <h2 id="today-heading" class="h4 fw-bold mb-3">Ringkasan Pekerjaan</h2>
                            <div class="row g-3">
                                @foreach ([
                                    ['Laporan dibuat', $todaySummary['created']],
                                    ['Laporan baru', $todaySummary['new']],
                                    ['Sedang diproses', $todaySummary['processing']],
                                    ['Selesai hari ini', $todaySummary['completed']],
                                    ['RW aktif', $todaySummary['active_rws']],
                                    ['RT aktif', $todaySummary['active_rts']],
                                ] as [$label, $value])
                                    <div class="col-6"><div class="information-tile rounded-3 p-3"><span class="text-secondary small d-block">{{ $label }}</span><strong class="fs-4">{{ number_format($value) }}</strong></div></div>
                                @endforeach
                            </div>
                        </div>
                    </section>
                </div>
                <div class="col-xl-6">
                    <section class="card dashboard-panel h-100 border-0 shadow-sm" aria-labelledby="attention-heading">
                        <div class="card-body p-4">
                            <p class="section-eyebrow mb-1">Prioritas</p>
                            <h2 id="attention-heading" class="h4 fw-bold mb-3">Perlu Perhatian</h2>
                            <div class="d-grid gap-2">
                                <a class="attention-link rounded-3 p-3 text-decoration-none" href="{{ route('kelurahan.reports.index', ['status' => \App\Enums\ReportStatus::NEW->value]) }}#laporan"><span class="text-body">{{ number_format($attentionSummary['new']) }} laporan baru menunggu tindak lanjut</span><span aria-hidden="true">→</span></a>
                                <a class="attention-link rounded-3 p-3 text-decoration-none" href="{{ route('kelurahan.reports.index', ['status' => \App\Enums\ReportStatus::PROCESSING->value]) }}#laporan"><span class="text-body">{{ number_format($attentionSummary['stale_processing']) }} laporan diproses lebih dari 3 hari</span><span aria-hidden="true">→</span></a>
                                <a class="attention-link rounded-3 p-3 text-decoration-none" href="{{ route('kelurahan.rws.index', ['status' => 'active']) }}"><span class="text-body">{{ number_format($attentionSummary['rws_without_active_rts']) }} RW belum memiliki RT aktif</span><span aria-hidden="true">→</span></a>
                                <div class="information-tile d-flex justify-content-between align-items-center rounded-3 p-3"><span>{{ number_format($attentionSummary['rts_without_active_officers']) }} RT belum memiliki petugas aktif</span><span class="badge text-bg-secondary">Informasi</span></div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <section class="card dashboard-panel border-0 shadow-sm mb-4" aria-labelledby="region-heading">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                        <div><p class="section-eyebrow mb-1">Wilayah desa</p><h2 id="region-heading" class="h4 fw-bold mb-0">Struktur Wilayah Ringkas</h2></div>
                        <a href="{{ route('kelurahan.rws.index') }}">Lihat seluruh RW</a>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge text-bg-success p-2">{{ $todaySummary['active_rws'] }} RW aktif</span>
                        <span class="badge text-bg-primary p-2">{{ $todaySummary['active_rts'] }} RT aktif</span>
                        <span class="badge text-bg-secondary p-2">{{ number_format($totalCitizens) }} warga</span>
                        <span class="badge text-bg-info p-2">{{ number_format($total) }} laporan</span>
                        @foreach (\App\Enums\ReportStatus::cases() as $status)
                            <span class="badge text-bg-{{ $status->bootstrapColor() }} p-2">{{ number_format($totalsByStatus[$status->value]) }} {{ $status->label() }}</span>
                        @endforeach
                    </div>
                    @if ($regionSummary->isEmpty())
                        <p class="text-secondary text-center py-4 mb-0">Belum ada RW aktif untuk ditampilkan.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead><tr><th>Kode</th><th>Nama RW</th><th>RT Aktif</th><th>Laporan</th><th><span class="visually-hidden">Aksi</span></th></tr></thead>
                                <tbody>
                                    @foreach ($regionSummary as $rw)
                                        @php($rwUrl = $canManageRws ? route('kelurahan.rws.edit', $rw) : route('kelurahan.rws.index'))
                                        <tr class="interactive-row" tabindex="0" data-row-url="{{ $rwUrl }}">
                                            <td class="fw-semibold" data-label="Kode">{{ $rw->code }}</td><td data-label="Nama RW">{{ $rw->name }}</td><td data-label="RT Aktif">{{ $rw->active_rts_count }}</td><td data-label="Laporan">{{ $rw->reports_count }}</td>
                                            <td data-label="Aksi"><a href="{{ $rwUrl }}" class="small text-decoration-none">Lihat detail <span aria-hidden="true">→</span></a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>

            <section id="laporan" class="card dashboard-panel border-0 shadow-sm" aria-labelledby="reports-heading">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
                        <div><p class="section-eyebrow mb-1">Monitoring layanan</p><h2 id="reports-heading" class="h4 fw-bold mb-0">Daftar Laporan</h2></div>
                        <form method="GET" action="{{ route('kelurahan.reports.index') }}#laporan" class="row g-2">
                            <div class="col-6 col-md-auto"><select name="rw_id" class="form-select" aria-label="Filter RW"><option value="">Semua RW</option>@foreach ($rws as $rw)<option value="{{ $rw->id }}" @selected((int) request('rw_id') === $rw->id)>{{ $rw->code }}</option>@endforeach</select></div>
                            <div class="col-6 col-md-auto"><select name="rt_id" class="form-select" aria-label="Filter RT"><option value="">Semua RT</option>@foreach ($rts as $rt)<option value="{{ $rt->id }}" @selected((int) request('rt_id') === $rt->id)>{{ $rt->code }}</option>@endforeach</select></div>
                            <div class="col-6 col-md-auto"><select name="status" class="form-select" aria-label="Filter status"><option value="">Semua Status</option>@foreach (\App\Enums\ReportStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
                            <div class="col-6 col-md"><input name="search" value="{{ request('search') }}" class="form-control" aria-label="Cari laporan" placeholder="Tiket, warga, atau judul"></div>
                            <div class="col-12 col-md-auto"><button class="btn btn-primary w-100">Cari</button></div>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Tiket</th><th>RW</th><th>RT</th><th>Warga</th><th>Judul</th><th>Status</th><th><span class="visually-hidden">Aksi</span></th></tr></thead>
                            <tbody>
                                @forelse ($reports as $report)
                                    <tr class="interactive-row" tabindex="0" data-row-url="{{ route($reportDetailRoute, $report) }}"><td data-label="Tiket">{{ $report->ticket_number }}</td><td data-label="RW">{{ $report->rt->rw->code }}</td><td data-label="RT">{{ $report->rt->code }}</td><td data-label="Warga">{{ $report->citizen->name }}</td><td data-label="Judul">{{ $report->title }}</td><td data-label="Status"><span class="badge text-bg-{{ $report->status->bootstrapColor() }}">{{ $report->status->label() }}</span></td><td data-label="Aksi"><a class="btn btn-outline-primary btn-sm" href="{{ route($reportDetailRoute, $report) }}" target="_blank" rel="noopener">Detail</a></td></tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-secondary py-5">Belum ada laporan yang sesuai dengan filter.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $reports->links('pagination::bootstrap-5') }}
                </div>
            </section>
        </main>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.kelurahan-dashboard [data-row-url]').forEach((row) => {
            const openRow = () => window.location.assign(row.dataset.rowUrl);

            row.addEventListener('click', (event) => {
                if (event.button === 0 && !(event.target instanceof Element && event.target.closest('a, button, input, select, textarea'))) {
                    openRow();
                }
            });
            row.addEventListener('keydown', (event) => {
                if (event.target === row && event.key === 'Enter') {
                    event.preventDefault();
                    openRow();
                }
            });
        });
    </script>
@endpush