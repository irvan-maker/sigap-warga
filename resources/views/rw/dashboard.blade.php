@extends('layouts.app')
@section('title', 'Dashboard RW - SIGAP WARGA')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">
    <style>
        /* ==========================================================
           SIGAP WARGA — RW Dashboard: Glassmorphism (Blue Accent)
           Font Family: Plus Jakarta Sans (Global Applied)
           Mirrors the Kelurahan dashboard glassmorphism pattern
           ========================================================== */
        .rw-dashboard,
        .rw-dashboard *,
        .rw-dashboard ::placeholder {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important;
        }

        .rw-dashboard {
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
            color: var(--gl-ink);
            background: linear-gradient(165deg, #eef2fc 0%, #e6ecfb 45%, #eef4fb 100%);
        }

        /* Floating blurred color blobs */
        .rw-dashboard .glass-blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            z-index: 0;
            pointer-events: none;
        }
        .rw-dashboard .glass-blob--1 {
            width: 440px; height: 440px;
            top: -10%; left: -8%;
            background: radial-gradient(circle, rgba(43,92,255,0.5), transparent 70%);
        }
        .rw-dashboard .glass-blob--2 {
            width: 400px; height: 400px;
            top: 22%; right: -12%;
            background: radial-gradient(circle, rgba(124,92,255,0.4), transparent 70%);
        }
        .rw-dashboard .glass-blob--3 {
            width: 480px; height: 480px;
            bottom: -15%; left: 20%;
            background: radial-gradient(circle, rgba(52,209,200,0.35), transparent 70%);
        }

        .rw-dashboard > .navbar { position: relative; z-index: 2; }
        .rw-dashboard > main { position: relative; z-index: 1; }

        /* ---------- Navbar ---------- */
        .rw-dashboard .navbar {
            background: rgba(255, 255, 255, 0.6) !important;
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.5) !important;
        }
        .rw-dashboard .navbar-brand {
            color: var(--gl-blue) !important;
            font-weight: 800;
        }
        .rw-dashboard .navbar .btn-outline-danger {
            background: rgba(255, 59, 59, 0.08);
            border: 1px solid rgba(255, 59, 59, 0.35);
            color: #d13a3a;
            backdrop-filter: blur(6px);
            font-weight: 600;
            border-radius: 10px;
            transition: all .2s ease;
        }
        .rw-dashboard .navbar .btn-outline-danger:hover {
            background: #ff3b3b;
            border-color: #ff3b3b;
            color: #fff;
        }

        /* ---------- Hero Banner ---------- */
        .rw-dashboard .dashboard-hero.card {
            background: linear-gradient(135deg, rgba(43, 92, 255, 0.6) 0%, rgba(94, 74, 255, 0.5) 100%) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 24px !important;
            box-shadow: 0 20px 50px rgba(31, 60, 136, 0.18) !important;
        }
        .rw-dashboard .dashboard-hero.card .badge {
            backdrop-filter: blur(6px);
            background: rgba(255, 255, 255, 0.18) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            color: #fff !important;
            font-weight: 700;
            border-radius: 50rem !important;
        }
        .rw-dashboard .dashboard-hero.card .hero-meta {
            background: rgba(255, 255, 255, 0.16) !important;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 16px !important;
            color: #fff;
        }

        /* ---------- Eyebrow ---------- */
        .rw-dashboard .section-eyebrow {
            text-transform: uppercase;
            font-weight: 800;
            font-size: .72rem;
            letter-spacing: .09em;
            color: var(--gl-blue) !important;
        }

        /* ---------- Cards & Panels (RW pakai .card polos, tanpa navigation-card) ---------- */
        .rw-dashboard .card {
            background: var(--gl-glass-bg) !important;
            backdrop-filter: blur(18px) saturate(180%);
            -webkit-backdrop-filter: blur(18px) saturate(180%);
            border: 1px solid var(--gl-glass-border) !important;
            border-radius: 20px !important;
            box-shadow: var(--gl-glass-shadow) !important;
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .rw-dashboard a.card:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 40px rgba(31, 60, 136, 0.2) !important;
            text-decoration: none;
        }
        .rw-dashboard .action-panel {
            background: linear-gradient(135deg, rgba(255,214,10,.35), rgba(255,90,138,.2)) !important;
            border-left: none !important;
        }

        /* ---------- Icon box ---------- */
        .rw-dashboard .icon-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--gl-blue), var(--gl-blue-light));
            color: #fff;
            box-shadow: 0 6px 16px rgba(43, 92, 255, 0.3);
        }

        /* ---------- Insight card ---------- */
        .rw-dashboard .insight-card {
            background: rgba(255, 255, 255, 0.5) !important;
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.65) !important;
            border-radius: 20px !important;
        }

        /* ---------- Statistik per-RT (pakai border.rounded-3, bukan card) ---------- */
        .rw-dashboard .border.rounded-3 {
            background: rgba(255, 255, 255, 0.45) !important;
            border: 1px solid rgba(255, 255, 255, 0.65) !important;
            backdrop-filter: blur(8px);
            border-radius: 14px !important;
        }

        /* ---------- Buttons ---------- */
        .rw-dashboard .btn {
            border-radius: 12px;
            font-weight: 700;
        }
        .rw-dashboard .btn-primary {
            background: linear-gradient(135deg, var(--gl-blue), var(--gl-blue-light));
            border: none;
            box-shadow: 0 8px 22px rgba(43, 92, 255, 0.35);
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .rw-dashboard .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(43, 92, 255, 0.45);
        }
        .rw-dashboard .btn-warning {
            background: linear-gradient(135deg, #ffd60a, #ff9f1c);
            border: none;
            color: #1c2340;
            box-shadow: 0 8px 22px rgba(255, 159, 28, 0.35);
        }
        .rw-dashboard .btn-outline-primary {
            background: rgba(43, 92, 255, 0.08);
            border: 1px solid rgba(43, 92, 255, 0.4);
            color: var(--gl-blue);
            backdrop-filter: blur(6px);
        }
        .rw-dashboard .btn-outline-primary:hover {
            background: var(--gl-blue);
            border-color: var(--gl-blue);
            color: #fff;
        }

        /* ---------- Forms ---------- */
        .rw-dashboard .form-select,
        .rw-dashboard .form-control {
            background: rgba(255, 255, 255, 0.6) !important;
            border: 1px solid rgba(43, 92, 255, 0.25) !important;
            border-radius: 12px !important;
            backdrop-filter: blur(6px);
            font-weight: 500;
        }
        .rw-dashboard .form-select:focus,
        .rw-dashboard .form-control:focus {
            border-color: var(--gl-blue) !important;
            box-shadow: 0 0 0 3px rgba(43, 92, 255, 0.15) !important;
            background: rgba(255, 255, 255, 0.85) !important;
        }

        /* ---------- Badges ---------- */
        .rw-dashboard .badge {
            font-weight: 700;
            backdrop-filter: blur(6px);
            border-radius: 8px;
            padding: .45em .7em;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .rw-dashboard .badge-status {
            border-radius: 50rem;
        }

        /* ---------- Tables ---------- */
        .rw-dashboard .table-responsive {
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(43, 92, 255, 0.15);
            background: rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(12px);
        }
        .rw-dashboard table.table {
            margin-bottom: 0;
        }
        .rw-dashboard table.table thead th {
            background: rgba(43, 92, 255, 0.08) !important;
            color: var(--gl-blue);
            text-transform: uppercase;
            font-size: .72rem;
            letter-spacing: .06em;
            font-weight: 700;
            border-bottom: 1px solid rgba(43, 92, 255, 0.15);
            padding: 1rem .9rem;
        }
        .rw-dashboard table.table tbody td {
            border-bottom: 1px solid rgba(43, 92, 255, 0.08);
            padding: .85rem .9rem;
            font-weight: 500;
        }
        .rw-dashboard .table-hover tbody tr:hover {
            background-color: rgba(43, 92, 255, 0.06);
        }
        .rw-dashboard [data-row-url] {
            cursor: pointer;
        }

        /* ---------- Empty state ---------- */
        .rw-dashboard .empty-state-icon {
            background: rgba(43, 92, 255, 0.1);
            color: var(--gl-blue);
        }

        /* ---------- Pagination ---------- */
        .rw-dashboard .pagination .page-link {
            border: 1px solid rgba(43, 92, 255, 0.25);
            border-radius: 10px;
            margin-inline: 3px;
            color: var(--gl-blue);
            font-weight: 600;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(6px);
        }
        .rw-dashboard .pagination .page-item.active .page-link {
            background: var(--gl-blue);
            border-color: var(--gl-blue);
            color: #fff;
        }
    </style>
@endpush

@section('content')
<div class="rw-dashboard min-vh-100">
    <span class="glass-blob glass-blob--1" aria-hidden="true"></span>
    <span class="glass-blob glass-blob--2" aria-hidden="true"></span>
    <span class="glass-blob glass-blob--3" aria-hidden="true"></span>

    <nav class="navbar bg-white border-bottom sticky-top" aria-label="Navigasi utama"><div class="container"><a class="navbar-brand fw-bold text-primary" href="{{ route('rw.dashboard') }}"><i class="bi bi-buildings me-2"></i>SIGAP WARGA <span class="text-secondary fw-normal">· RW</span></a><form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-box-arrow-right me-1"></i>Keluar</button></form></div></nav>
    <main id="main-content" class="container app-main">
        <header class="dashboard-hero card border-0 overflow-hidden position-relative text-white mb-4">
            <div class="card-body position-relative p-4 p-lg-5 d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-4" style="z-index:1">
                <div>
                    <p class="text-white-75 text-uppercase small fw-bold mb-2">Dashboard pelayanan wilayah</p>
                    <h1 class="display-6 fw-bold mb-2">Ringkasan RW</h1>
                    <p class="text-white-75 mb-0">Pantau laporan, administrasi warga, dan koordinasi seluruh RT dalam satu layar.</p>
                </div>
                <div class="hero-meta rounded-3 p-3">
                    <span class="small text-white-50 d-block">Hari ini</span>
                    <strong>{{ now()->timezone('Asia/Jakarta')->locale('id')->isoFormat('dddd, D MMMM Y') }}</strong>
                </div>
            </div>
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

        <section class="mb-4" aria-labelledby="rw-kpi"><div class="d-flex justify-content-between align-items-end mb-3"><div><p class="section-eyebrow mb-1">Kinerja wilayah</p><h2 id="rw-kpi" class="h4 section-title mb-0">Indikator Utama</h2></div></div><div class="row g-3"><div class="col-6 col-lg"><div class="card h-100"><div class="card-body"><span class="icon-box mb-3"><i class="bi bi-inbox"></i></span><div class="text-secondary small">Total Laporan</div><div class="fs-2 fw-bold">{{ $total }}</div></div></div></div>@foreach(\App\Enums\ReportStatus::cases() as $status)<div class="col-6 col-lg"><div class="card h-100"><div class="card-body"><div class="text-secondary small">{{ $status->value }}</div><div class="fs-2 fw-bold">{{ $totalsByStatus[$status->value] }}</div></div></div></div>@endforeach<div class="col-6 col-lg"><div class="card h-100"><div class="card-body"><div class="text-secondary small">RT Aktif</div><div class="fs-2 fw-bold">{{ $activeRtCount }}</div></div></div></div></div></section>

        <section class="mb-4" aria-labelledby="rw-actions"><p class="section-eyebrow mb-1">Navigasi layanan</p><h2 id="rw-actions" class="h4 section-title mb-3">Aksi Cepat</h2><div class="row g-3"><div class="col-sm-6 col-xl-3"><a class="card h-100 text-decoration-none" href="{{ route('rw.rts.index') }}"><span class="card-body d-flex gap-3"><span class="icon-box"><i class="bi bi-diagram-3"></i></span><span><strong class="d-block">Kelola RT</strong><small class="text-secondary">Struktur wilayah</small></span></span></a></div><div class="col-sm-6 col-xl-3"><a class="card h-100 text-decoration-none" href="{{ route('rw.letters.index') }}"><span class="card-body d-flex gap-3"><span class="icon-box"><i class="bi bi-envelope-check"></i></span><span><strong class="d-block">Verifikasi Surat</strong><small class="text-secondary">{{ $letterCount }} menunggu</small></span></span></a></div><div class="col-sm-6 col-xl-3"><a class="card h-100 text-decoration-none" href="{{ route('rw.citizens.index') }}"><span class="card-body d-flex gap-3"><span class="icon-box"><i class="bi bi-people"></i></span><span><strong class="d-block">Monitoring Warga</strong><small class="text-secondary">{{ $activeCitizenCount }} aktif</small></span></span></a></div><div class="col-sm-6 col-xl-3"><a class="card h-100 text-decoration-none" href="{{ route('rw.family-cards.index') }}"><span class="card-body d-flex gap-3"><span class="icon-box"><i class="bi bi-card-heading"></i></span><span><strong class="d-block">Monitoring KK</strong><small class="text-secondary">{{ $activeFamilyCardCount }} aktif</small></span></span></a></div></div></section>

        <section class="card action-panel border-0 mb-4" aria-labelledby="rw-attention"><div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3"><div><p class="section-eyebrow mb-1">Tindak lanjut</p><h2 id="rw-attention" class="h4 section-title mb-1">Perlu Perhatian</h2><p class="text-secondary mb-0">{{ $letterCount }} pengajuan surat perlu ditinjau oleh petugas RW.</p></div><a class="btn btn-warning" href="{{ route('rw.letters.index') }}"><i class="bi bi-exclamation-circle me-1"></i>Tinjau Surat</a></div></section>

        <section class="card border-0 mb-4" aria-labelledby="rw-latest"><div class="card-header bg-white p-4 border-0"><div class="d-flex flex-wrap justify-content-between align-items-end gap-3"><div><p class="section-eyebrow mb-1">Aktivitas terbaru</p><h2 id="rw-latest" class="h4 section-title mb-0">Laporan Terbaru</h2></div><form method="GET" action="{{ route('rw.dashboard') }}" class="row g-2"><div class="col-sm-auto"><select name="rt_id" class="form-select" aria-label="Filter RT"><option value="">Semua RT</option>@foreach($rts as $rt)<option value="{{ $rt->id }}" @selected((int) request('rt_id') === $rt->id)>{{ $rt->code }}</option>@endforeach</select></div><div class="col-sm-auto"><select name="status" class="form-select" aria-label="Filter status"><option value="">Semua status</option>@foreach(\App\Enums\ReportStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->value }}</option>@endforeach</select></div><div class="col-sm-auto"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Tiket, warga, atau judul" aria-label="Cari laporan"></div><div class="col-sm-auto"><button class="btn btn-primary" type="submit"><i class="bi bi-search"></i><span class="visually-hidden">Terapkan filter</span></button></div></form></div></div><div class="table-responsive"><table class="table table-hover table-sticky align-middle mb-0"><thead><tr><th>Tiket</th><th>RT</th><th>Warga</th><th>Judul</th><th>Status</th><th><span class="visually-hidden">Aksi</span></th></tr></thead><tbody>@forelse($reports as $report)@php($url=route('rw.reports.show',$report))<tr data-row-url="{{ $url }}"><td class="fw-semibold">{{ $report->ticket_number }}</td><td>{{ $report->rt->code }}</td><td>{{ $report->citizen->name }}</td><td>{{ $report->title }}</td><td><span class="badge badge-status text-bg-{{ $report->status->bootstrapColor() }}">{{ $report->status->value }}</span></td><td><a class="btn btn-outline-primary btn-sm" href="{{ $url }}" aria-label="Detail {{ $report->ticket_number }}"><i class="bi bi-chevron-right"></i></a></td></tr>@empty<tr><td colspan="6"><div class="empty-state"><span class="empty-state-icon"><i class="bi bi-clipboard-check"></i></span><h3 class="h5">Belum ada laporan</h3><p class="text-secondary mb-3">Aktivitas laporan terbaru akan tampil di sini.</p><a class="btn btn-outline-primary" href="{{ route('rw.dashboard') }}">Reset Filter</a></div></td></tr>@endforelse</tbody></table></div>@if($reports->hasPages())<div class="card-footer bg-white p-3">{{ $reports->links('pagination::bootstrap-5') }}</div>@endif</section>

        <section class="card border-0" aria-labelledby="rw-stats"><div class="card-body p-4"><p class="section-eyebrow mb-1">Statistik</p><h2 id="rw-stats" class="h4 section-title mb-3">Total Laporan per RT</h2><div class="row g-3">@forelse($rts as $rt)<div class="col-sm-6 col-lg-4"><div class="border rounded-3 p-3 h-100"><div class="text-secondary">{{ $rt->code }} · {{ $rt->name }}</div><div class="fs-3 fw-bold">{{ $totalsByRt[$rt->id] }}</div><small class="text-secondary">laporan tercatat</small></div></div>@empty<div class="col-12"><div class="empty-state"><span class="empty-state-icon"><i class="bi bi-diagram-3"></i></span><h3 class="h5">Belum ada data RT</h3><p class="text-secondary">Tambahkan RT untuk melihat statistik wilayah.</p><a class="btn btn-primary" href="{{ route('rw.rts.create') }}">Tambah RT</a></div></div>@endforelse</div></div></section>
        @include('analytics.rw')
    </main>
</div>
@endsection