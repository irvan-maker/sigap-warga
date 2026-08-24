@extends('layouts.app')

@section('title', config('village.name').' - Portal Resmi SIGAP WARGA')

@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        :root {
            --sigap-lime: #b8ff5f;
            --sigap-lime-strong: #9ef044;
            --sigap-lime-soft: #ebffd2;
            --sigap-purple: #5c288c;
            --sigap-purple-dark: #4b1f75;
            --sigap-purple-soft: #efe4fb;
            --sigap-bg: #f8f8fb;
            --sigap-card: #ffffff;
            --sigap-text: #222126;
            --sigap-muted: #726c78;
            --sigap-border: #ddd7e3;
        }

        body {
            background: var(--sigap-bg);
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            font-size: 16px;
        }

        .citizen-portal,
        .citizen-portal button,
        .citizen-portal input,
        .citizen-portal select,
        .citizen-portal textarea {
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
        }

        .citizen-portal {
            color: var(--sigap-text);
            background: var(--sigap-bg);
        }

        /* HEADER */
        .citizen-header {
            z-index: 1030;
            background: rgba(255,255,255,.96);
            border-bottom: 1px solid var(--sigap-border);
            backdrop-filter: blur(12px);
        }

        .citizen-header .navbar {
            min-height: 72px;
        }

        .citizen-brand {
            display: inline-flex;
            align-items: center;
            gap: 11px;
            color: var(--sigap-text);
            text-decoration: none;
        }

        .citizen-brand:hover {
            color: var(--sigap-purple);
        }

        .citizen-brand-mark {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 11px;
            background: var(--sigap-purple);
            color: var(--sigap-lime);
            font-size: 20px;
            box-shadow: 0 8px 18px rgba(92,40,140,.13);
        }

        .citizen-brand-copy strong,
        .citizen-brand-copy small {
            display: block;
        }

        .citizen-brand-copy strong {
            color: var(--sigap-purple-dark);
            font-size: 16px;
            line-height: 1.05;
            font-weight: 800;
        }

        .citizen-brand-copy small {
            margin-top: 4px;
            color: var(--sigap-muted);
            font-size: 11.5px;
            line-height: 1.1;
        }

        .citizen-header .nav-link {
            color: #5f5864;
            font-size: 14px;
            font-weight: 700;
        }

        .citizen-header .nav-link:hover {
            color: var(--sigap-purple);
        }

        .citizen-header .btn-primary {
            min-height: 40px;
            border-color: var(--sigap-purple);
            border-radius: 9px;
            background: var(--sigap-purple);
            font-size: 13px;
            font-weight: 800;
        }

        .citizen-header .btn-primary:hover {
            border-color: var(--sigap-purple-dark);
            background: var(--sigap-purple-dark);
        }

        .citizen-header .btn-outline-primary {
            min-height: 40px;
            border-color: #8b61ac;
            border-radius: 9px;
            color: var(--sigap-purple);
            font-size: 13px;
            font-weight: 800;
        }

        .citizen-header .btn-outline-primary:hover {
            border-color: var(--sigap-purple);
            background: var(--sigap-purple);
            color: #fff;
        }

        /* HERO */
        .citizen-hero {
            position: relative;
            overflow: hidden;
            padding: 78px 0 72px;
            background:
                radial-gradient(120% 90% at 82% 8%, rgba(255,255,255,.22) 0%, rgba(255,255,255,0) 34%),
                radial-gradient(90% 80% at 72% 100%, rgba(255,255,255,.15) 0%, rgba(255,255,255,0) 42%),
                linear-gradient(135deg, #4f1f7d 0%, #653292 55%, #5b2889 100%);
            color: #fff;
        }

        .citizen-hero::after {
            content: '';
            position: absolute;
            inset: 0;
            opacity: .11;
            background-image: radial-gradient(circle, rgba(255,255,255,.9) 1px, transparent 1.3px);
            background-size: 22px 22px;
            pointer-events: none;
        }

        .citizen-hero .container {
            position: relative;
            z-index: 1;
        }

        .citizen-hero-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 11px;
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 999px;
            background: rgba(255,255,255,.10);
            color: var(--sigap-lime);
            font-size: 11.5px;
            font-weight: 800;
            letter-spacing: .02em;
        }

        .citizen-hero h1 {
            max-width: 760px;
            margin: 22px 0 0;
            font-size: clamp(38px, 5.2vw, 62px);
            line-height: 1.03;
            font-weight: 800;
            letter-spacing: -.04em;
        }

        .citizen-hero .lead {
            max-width: 720px;
            color: rgba(255,255,255,.86);
            font-size: 17px;
            line-height: 1.7;
        }

        .citizen-hero .btn-light,
        .citizen-hero .btn-outline-light {
            min-height: 48px;
            display: inline-flex;
            align-items: center;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 800;
        }

        .citizen-hero .btn-light {
            border-color: var(--sigap-lime);
            background: var(--sigap-lime);
            color: #3b1859;
        }

        .citizen-hero .btn-light:hover {
            border-color: var(--sigap-lime-strong);
            background: var(--sigap-lime-strong);
            color: #32124e;
        }

        .citizen-trust-card {
            padding: 22px;
            border: 1px solid rgba(255,255,255,.20);
            border-radius: 16px;
            background: rgba(255,255,255,.10);
            box-shadow: 0 20px 42px rgba(32,10,52,.16);
            backdrop-filter: blur(8px);
        }

        .citizen-trust-item {
            display: grid;
            grid-template-columns: 42px 1fr;
            gap: 13px;
            align-items: start;
            padding: 15px 0;
        }

        .citizen-trust-item + .citizen-trust-item {
            border-top: 1px solid rgba(255,255,255,.14);
        }

        .citizen-trust-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: rgba(184,255,95,.15);
            color: var(--sigap-lime);
            font-size: 19px;
        }

        .citizen-trust-item strong {
            display: block;
            font-size: 14px;
            font-weight: 800;
        }

        .citizen-trust-item small {
            font-size: 11.5px;
            line-height: 1.55;
        }

        /* SECTIONS */
        .citizen-section {
            padding: 68px 0;
        }

        .citizen-section-soft {
            background: #f2eef6;
        }

        .section-eyebrow {
            color: var(--sigap-purple);
            font-size: 11.5px;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .section-title {
            color: var(--sigap-text);
            font-size: clamp(27px, 3vw, 36px);
            line-height: 1.15;
            font-weight: 800;
            letter-spacing: -.025em;
        }

        .citizen-section .text-secondary {
            color: var(--sigap-muted) !important;
            font-size: 14px;
            line-height: 1.65;
        }

        /* SERVICE CARDS */
        .citizen-service-card {
            min-height: 255px;
            display: flex;
            flex-direction: column;
            height: 100%;
            padding: 22px;
            border: 1px solid var(--sigap-border);
            border-radius: 14px;
            background: #fff;
            color: var(--sigap-text);
            text-decoration: none;
            box-shadow: 0 3px 9px rgba(46,31,60,.035);
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .citizen-service-card:hover {
            transform: translateY(-3px);
            border-color: #c9b8d5;
            box-shadow: 0 12px 28px rgba(71,40,94,.08);
            color: var(--sigap-text);
        }

        .citizen-service-icon {
            width: 45px;
            height: 45px;
            display: grid;
            place-items: center;
            margin-bottom: 18px;
            border-radius: 11px;
            background: var(--sigap-purple-soft);
            color: var(--sigap-purple);
            font-size: 20px;
        }

        .citizen-service-card h3 {
            margin-bottom: 9px;
            font-size: 17px;
            font-weight: 800;
        }

        .citizen-service-card small {
            color: var(--sigap-muted);
            font-size: 12.5px;
            line-height: 1.6;
        }

        .service-link {
            display: inline-flex;
            align-items: center;
            margin-top: auto;
            padding-top: 18px;
            color: var(--sigap-purple);
            font-size: 12.5px;
            font-weight: 800;
        }

        /* STEPS */
        .setup-step {
            display: grid;
            grid-template-columns: 44px 1fr;
            gap: 14px;
            padding: 18px;
            border: 1px solid var(--sigap-border);
            border-radius: 13px;
            background: #fff;
        }

        .setup-step-number {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: var(--sigap-purple);
            color: #fff;
            font-size: 13px;
            font-weight: 800;
        }

        .setup-step-body h3 {
            margin: 0 0 5px;
            color: var(--sigap-text);
            font-size: 15px;
            font-weight: 800;
        }

        .setup-step-body p {
            color: var(--sigap-muted);
            font-size: 12.5px;
            line-height: 1.6;
        }

        .citizen-section .alert-success {
            border: 1px solid #cfeeb2 !important;
            border-radius: 10px;
            background: var(--sigap-lime-soft);
            color: #4f7415;
            font-size: 13px;
        }

        /* STATS */
        .citizen-stat {
            height: 100%;
            padding: 20px;
            border: 1px solid var(--sigap-border);
            border-radius: 13px;
            background: #fff;
            box-shadow: 0 2px 7px rgba(46,31,60,.035);
        }

        .citizen-stat strong {
            display: block;
            margin-bottom: 7px;
            color: var(--sigap-purple-dark);
            font-size: 30px;
            line-height: 1;
            font-weight: 800;
        }

        .citizen-stat span,
        .citizen-stat p {
            color: var(--sigap-muted) !important;
            font-size: 12px !important;
        }

        .dashboard-panel-modern {
            overflow: hidden;
            border: 1px solid var(--sigap-border);
            border-radius: 14px;
            box-shadow: 0 2px 7px rgba(46,31,60,.035);
        }

        .dashboard-panel-modern .card-header {
            border-bottom: 1px solid #eee9f1;
            background: #fff;
        }

        .dashboard-panel-modern .card-header h3 {
            color: var(--sigap-text);
            font-size: 14px;
            font-weight: 800;
        }

        .dashboard-panel-modern thead th {
            background: #faf9fb;
            color: #6f6874;
            font-size: 12px;
            font-weight: 800;
            border-bottom-color: #e6e0e9;
        }

        .dashboard-panel-modern tbody td,
        .dashboard-panel-modern tbody th {
            color: #413b45;
            font-size: 13px;
            border-bottom-color: #f0edf2;
        }

        /* FOOTER */
        .citizen-footer {
            padding: 24px 0;
            background: #311548;
            color: #fff;
        }

        .citizen-footer span {
            font-size: 12.5px;
        }

        .citizen-footer .text-white-50 {
            color: rgba(255,255,255,.62) !important;
        }

        @media (max-width: 991.98px) {
            .citizen-header .navbar-collapse {
                padding: 12px 0 8px;
            }

            .citizen-header .nav-link {
                padding: 10px 0;
            }

            .citizen-hero {
                padding: 58px 0 55px;
            }
        }

        @media (max-width: 767.98px) {
            body {
                font-size: 15px;
            }

            .citizen-hero h1 {
                font-size: clamp(34px, 10vw, 46px);
            }

            .citizen-hero .lead {
                font-size: 15px;
            }

            .citizen-section {
                padding: 50px 0;
            }

            .citizen-service-card {
                min-height: 225px;
            }
        }
    </style>

    <div class="citizen-portal">
        <header class="citizen-header sticky-top">
            <nav class="navbar navbar-expand-lg" aria-label="Navigasi portal warga">
                <div class="container py-2">
                    <a class="navbar-brand citizen-brand" href="{{ route('public.home') }}">
                        <span class="citizen-brand-mark" aria-hidden="true">
                            <i class="bi bi-flower1"></i>
                        </span>

                        <span class="citizen-brand-copy">
                            <strong>SIGAP WARGA</strong>
                            <small>{{ config('village.name') }}</small>
                        </span>
                    </a>

                    <button
                        class="navbar-toggler"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#citizenNavigation"
                        aria-controls="citizenNavigation"
                        aria-expanded="false"
                        aria-label="Buka navigasi"
                    >
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div id="citizenNavigation" class="collapse navbar-collapse">
                        <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                            <li class="nav-item">
                                <a class="nav-link" href="#layanan">Layanan</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#cara-kerja">Cara Kerja</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#statistik">Transparansi</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#kontak">Kontak Resmi</a>
                            </li>
                            <li class="nav-item ms-lg-2">
                                @auth
                                    <a class="btn btn-primary" href="{{ route('dashboard') }}">
                                        <i class="bi bi-grid me-1" aria-hidden="true"></i>
                                        Masuk Dashboard
                                    </a>
                                @else
                                    <a class="btn btn-outline-primary" href="{{ route('login') }}">
                                        Login Petugas
                                    </a>
                                @endauth
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        </header>

        <main id="main-content">
            <section class="citizen-hero">
                <div class="container">
                    <div class="row align-items-center g-5">
                        <div class="col-lg-7">
                            <span class="citizen-hero-kicker">
                                <i class="bi bi-patch-check-fill" aria-hidden="true"></i>
                                Portal layanan resmi {{ config('village.name') }}
                            </span>

                            <h1>Lapor lebih mudah, penanganan tetap mengikuti wilayah.</h1>

                            <p class="lead mt-4">
                                Akses layanan Desa Curug Sangereng dari satu portal: ajukan surat secara daring,
                                lacak proses administrasi, dan gunakan QR resmi wilayah untuk laporan cepat melalui WhatsApp.
                            </p>

                            <div class="d-flex flex-wrap gap-2 mt-4">
                                <a class="btn btn-light btn-lg" href="{{ route('public.letter-submissions.index') }}">
                                    <i class="bi bi-file-earmark-plus me-2" aria-hidden="true"></i>
                                    Ajukan Surat
                                </a>

                                <a class="btn btn-outline-light btn-lg" href="{{ route('tracking.index') }}">
                                    <i class="bi bi-search me-2" aria-hidden="true"></i>
                                    Lacak Laporan
                                </a>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <aside class="citizen-trust-card" aria-label="Jaminan keamanan layanan">
                                <div class="citizen-trust-item">
                                    <span class="citizen-trust-icon">
                                        <i class="bi bi-qr-code-scan" aria-hidden="true"></i>
                                    </span>
                                    <div>
                                        <strong>1 RT, 1 QR resmi</strong>
                                        <small class="d-block text-white-50 mt-1">
                                            Portal menampilkan wilayah sebelum warga diarahkan ke WhatsApp.
                                        </small>
                                    </div>
                                </div>

                                <div class="citizen-trust-item">
                                    <span class="citizen-trust-icon">
                                        <i class="bi bi-cash-coin" aria-hidden="true"></i>
                                    </span>
                                    <div>
                                        <strong>Tidak dipungut biaya</strong>
                                        <small class="d-block text-white-50 mt-1">
                                            Petugas tidak pernah meminta OTP, PIN, kata sandi, atau transfer uang.
                                        </small>
                                    </div>
                                </div>

                                <div class="citizen-trust-item">
                                    <span class="citizen-trust-icon">
                                        <i class="bi bi-diagram-3" aria-hidden="true"></i>
                                    </span>
                                    <div>
                                        <strong>Hierarki tetap terjaga</strong>
                                        <small class="d-block text-white-50 mt-1">
                                            RT menangani lebih dulu, lalu meneruskan ke RW atau kelurahan bila diperlukan.
                                        </small>
                                    </div>
                                </div>
                            </aside>
                        </div>
                    </div>
                </div>
            </section>

            <section id="layanan" class="citizen-section" aria-labelledby="services-heading">
                <div class="container">
                    <p class="section-eyebrow mb-2">Layanan Warga</p>

                    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
                        <div>
                            <h2 id="services-heading" class="section-title mb-2">Pilih kebutuhan Anda</h2>
                            <p class="text-secondary mb-0">
                                Akses sederhana dengan petunjuk yang jelas di setiap tahap.
                            </p>
                        </div>

                        <span class="badge rounded-pill px-3 py-2"
                              style="background:var(--sigap-purple-soft);color:var(--sigap-purple-dark);">
                            Layanan warga Desa Curug Sangereng
                        </span>
                    </div>

                    <div class="row g-4">
                        @foreach ([
                            ['bi-file-earmark-plus', 'Ajukan Surat', 'Pilih jenis surat, lengkapi formulir dan persyaratan, lalu kirim pengajuan tanpa membuat akun.', route('public.letter-submissions.index'), 'Buka Persuratan'],
                            ['bi-file-earmark-check', 'Lacak Surat', 'Gunakan kode tracking dan nomor HP untuk memantau proses sampai surat diterbitkan.', route('letter-tracking.index'), 'Lacak surat'],
                            ['bi-search', 'Lacak Laporan', 'Masukkan nomor laporan dan nomor HP/WhatsApp untuk melihat perkembangan penanganan.', route('tracking.index'), 'Buka pelacakan'],
                            ['bi-qr-code-scan', 'Laporan Cepat', 'Scan QR resmi wilayah RT, periksa identitas wilayah, lalu lanjutkan laporan melalui WhatsApp.', '#cara-kerja', 'Pelajari alurnya'],
                        ] as [$icon, $title, $description, $url, $label])
                            <div class="col-sm-6 col-xl-3">
                                <a class="citizen-service-card" href="{{ $url }}">
                                    <span class="citizen-service-icon">
                                        <i class="bi {{ $icon }}" aria-hidden="true"></i>
                                    </span>

                                    <h3>{{ $title }}</h3>
                                    <small>{{ $description }}</small>

                                    <span class="service-link">
                                        {{ $label }}
                                        <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                                    </span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="cara-kerja" class="citizen-section citizen-section-soft" aria-labelledby="workflow-heading">
                <div class="container">
                    <div class="row g-5 align-items-start">
                        <div class="col-lg-5">
                            <p class="section-eyebrow mb-2">Alur Laporan Cepat</p>
                            <h2 id="workflow-heading" class="section-title">
                                Tiga langkah yang mudah diverifikasi
                            </h2>

                            <p class="text-secondary mt-3">
                                Jangan memindai QR yang tertutup stiker, rusak, atau mengarah ke domain selain domain resmi SIGAP WARGA.
                            </p>

                            <div class="alert alert-success border-0 mt-4 mb-0">
                                <i class="bi bi-shield-check me-2" aria-hidden="true"></i>
                                <strong>Domain resmi:</strong>
                                {{ parse_url(config('app.url'), PHP_URL_HOST) ?: config('app.url') }}
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="d-grid gap-3">
                                @foreach ([
                                    ['1', 'Scan QR wilayah', 'Gunakan QR resmi yang dipasang pengurus RT. Setiap RT memiliki satu pintu masuk yang berbeda.'],
                                    ['2', 'Periksa identitas portal', 'Pastikan domain, nama kelurahan, RT, dan RW sesuai sebelum menekan tombol menuju WhatsApp.'],
                                    ['3', 'Tulis laporan dengan bahasa biasa', 'Sampaikan kejadian, lokasi, dan kondisi. Sistem akan mencatat laporan ke wilayah yang benar.'],
                                ] as [$number, $title, $description])
                                    <div class="setup-step">
                                        <span class="setup-step-number">{{ $number }}</span>

                                        <div class="setup-step-body">
                                            <h3>{{ $title }}</h3>
                                            <p class="mb-0">{{ $description }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="statistik" class="citizen-section" aria-labelledby="statistics-heading">
                <div class="container">
                    <p class="section-eyebrow mb-2">Transparansi Pelayanan</p>
                    <h2 id="statistics-heading" class="section-title mb-4">Ringkasan layanan publik</h2>

                    <div class="row g-3">
                        @foreach ([
                            ['Total laporan', $statistics['total_reports']],
                            ['Laporan selesai', $statistics['completed_reports']],
                            ['Sedang diproses', $statistics['processing_reports']],
                            ['Surat diterbitkan', $statistics['issued_letters']],
                            ['Penyelesaian', $statistics['completion_percentage'].'%'],
                        ] as [$label, $value])
                            <div class="col-6 col-lg">
                                <div class="citizen-stat">
                                    <strong>{{ $value }}</strong>
                                    <span>{{ $label }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="card dashboard-panel-modern mt-4">
                        <div class="card-header px-4 py-3">
                            <h3 class="mb-0">Aktivitas enam bulan terakhir</h3>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Bulan</th>
                                        <th>Laporan</th>
                                        <th>Pengajuan surat</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach($trend as $month)
                                        <tr>
                                            <th scope="row">{{ $month['label'] }}</th>
                                            <td>{{ $month['reports'] }}</td>
                                            <td>{{ $month['letters'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <section id="kontak" class="citizen-section citizen-section-soft" aria-labelledby="contact-heading">
                <div class="container">
                    <div class="row g-5">
                        <div class="col-lg-5">
                            <p class="section-eyebrow mb-2">Bantuan Resmi</p>
                            <h2 id="contact-heading" class="section-title">Kontak dan pelayanan</h2>

                            <p class="text-secondary mt-3">
                                Gunakan informasi berikut untuk memverifikasi komunikasi yang mengatasnamakan SIGAP WARGA.
                            </p>
                        </div>

                        <div class="col-lg-7">
                            <div class="row g-3">
                                @foreach ([
                                    ['bi-clock', 'Jam pelayanan', config('village.service_hours') ?: 'Informasi belum tersedia.'],
                                    ['bi-geo-alt', 'Alamat kantor', config('village.office_address') ?: 'Silakan hubungi kantor kelurahan.'],
                                    ['bi-telephone', 'Telepon', config('village.contact_phone') ?: 'Belum tersedia'],
                                    ['bi-envelope', 'Email', config('village.email') ?: 'Belum tersedia'],
                                ] as [$icon, $label, $value])
                                    <div class="col-md-6">
                                        <div class="citizen-stat">
                                            <i class="bi {{ $icon }} me-2" style="color:var(--sigap-purple);" aria-hidden="true"></i>
                                            <strong class="d-inline fs-6">{{ $label }}</strong>
                                            <p class="mt-2 mb-0">{{ $value }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="citizen-footer">
            <div class="container d-flex flex-wrap justify-content-between gap-2">
                <span>© {{ now()->year }} {{ config('village.name') }}</span>
                <span class="text-white-50">Portal resmi SIGAP WARGA</span>
            </div>
        </footer>
    </div>
@endsection