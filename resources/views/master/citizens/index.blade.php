@extends('layouts.app')
@section('title', 'Data Warga')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ==========================================================
           SIGAP WARGA — Data Warga: Glassmorphism (blue accent)
           Sama persis dengan sistem desain Dashboard RT / Kelurahan
           ========================================================== */
        .citizens-page {
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

        .citizens-page h1,
        .citizens-page h2 {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
        }

        /* floating blurred color blobs = the "glass" needs something to refract */
        .citizens-page .glass-blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            z-index: 0;
            pointer-events: none;
        }
        .citizens-page .glass-blob--1 {
            width: 420px; height: 420px;
            top: -12%; left: -10%;
            background: radial-gradient(circle, rgba(43,92,255,0.5), transparent 70%);
        }
        .citizens-page .glass-blob--2 {
            width: 380px; height: 380px;
            top: 25%; right: -14%;
            background: radial-gradient(circle, rgba(124,92,255,0.4), transparent 70%);
        }
        .citizens-page .glass-blob--3 {
            width: 420px; height: 420px;
            bottom: -16%; left: 20%;
            background: radial-gradient(circle, rgba(52,209,200,0.35), transparent 70%);
        }

        .citizens-page main.app-main {
            position: relative;
            z-index: 1;
        }

        /* ---------- Breadcrumb ---------- */
        .citizens-page .breadcrumb-item a {
            color: var(--gl-blue);
            font-weight: 600;
            text-decoration: none;
        }
        .citizens-page .breadcrumb-item a:hover { text-decoration: underline; }
        .citizens-page .breadcrumb-item.active { color: var(--gl-muted); }

        /* ---------- Page header ---------- */
        .citizens-page .section-eyebrow {
            text-transform: uppercase;
            font-weight: 800;
            font-size: .72rem;
            letter-spacing: .09em;
            color: var(--gl-blue);
        }
        .citizens-page .section-title {
            font-weight: 800;
        }
        .citizens-page .page-icon {
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: rgba(43, 92, 255, 0.14);
            border: 1px solid rgba(43, 92, 255, 0.3);
            color: var(--gl-blue);
            font-size: 1.2rem;
            backdrop-filter: blur(6px);
        }

        /* ---------- Alert ---------- */
        .citizens-page .alert-success {
            background: rgba(20, 184, 166, 0.12);
            border: 1px solid rgba(20, 184, 166, 0.3);
            color: #0f7a6d;
            border-radius: 14px;
            backdrop-filter: blur(6px);
            font-weight: 600;
        }

        /* ---------- Glass cards ---------- */
        .citizens-page .card {
            background: var(--gl-glass-bg) !important;
            backdrop-filter: blur(18px) saturate(180%);
            -webkit-backdrop-filter: blur(18px) saturate(180%);
            border: 1px solid var(--gl-glass-border) !important;
            border-radius: 20px !important;
            box-shadow: var(--gl-glass-shadow) !important;
        }

        /* ---------- Forms ---------- */
        .citizens-page .form-select,
        .citizens-page .form-control {
            background: rgba(255, 255, 255, 0.6) !important;
            border: 1px solid rgba(43, 92, 255, 0.25) !important;
            border-radius: 12px !important;
            backdrop-filter: blur(6px);
            font-weight: 500;
        }
        .citizens-page .form-select:focus,
        .citizens-page .form-control:focus {
            border-color: var(--gl-blue) !important;
            box-shadow: 0 0 0 3px rgba(43, 92, 255, 0.15) !important;
            background: rgba(255, 255, 255, 0.85) !important;
        }

        /* ---------- Buttons ---------- */
        .citizens-page .btn {
            border-radius: 12px;
            font-weight: 700;
        }
        .citizens-page .btn-primary {
            background: linear-gradient(135deg, var(--gl-blue), var(--gl-blue-light));
            border: none;
            box-shadow: 0 8px 22px rgba(43, 92, 255, 0.35);
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .citizens-page .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(43, 92, 255, 0.45);
        }
        .citizens-page .btn-outline-primary {
            background: rgba(43, 92, 255, 0.08);
            border: 1px solid rgba(43, 92, 255, 0.4);
            color: var(--gl-blue);
            backdrop-filter: blur(6px);
        }
        .citizens-page .btn-outline-primary:hover {
            background: var(--gl-blue);
            border-color: var(--gl-blue);
            color: #fff;
        }
        .citizens-page .btn-outline-secondary {
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(28, 35, 64, 0.2);
            color: var(--gl-ink);
            backdrop-filter: blur(6px);
        }
        .citizens-page .btn-outline-secondary:hover {
            background: rgba(255, 255, 255, 0.8);
        }

        /* ---------- Badges ---------- */
        .citizens-page .badge {
            font-weight: 700;
            backdrop-filter: blur(6px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        /* ---------- Table ---------- */
        .citizens-page .table-responsive {
            border-radius: 16px;
            overflow: hidden;
        }
        .citizens-page table.table thead th {
            background: rgba(43, 92, 255, 0.08);
            color: var(--gl-blue);
            text-transform: uppercase;
            font-size: .72rem;
            letter-spacing: .06em;
            font-weight: 700;
            border-bottom: 1px solid rgba(43, 92, 255, 0.15);
            padding: .9rem .85rem;
        }
        .citizens-page table.table tbody td {
            border-bottom: 1px solid rgba(43, 92, 255, 0.08);
            padding: .8rem .85rem;
            font-weight: 500;
        }
        .citizens-page .table-hover tbody tr:hover {
            background-color: rgba(43, 92, 255, 0.06);
            cursor: pointer;
        }

        /* ---------- Pagination ---------- */
        .citizens-page .pagination .page-link {
            border: 1px solid rgba(43, 92, 255, 0.25);
            border-radius: 10px;
            margin-inline: 3px;
            font-weight: 600;
            color: var(--gl-blue);
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(6px);
        }
        .citizens-page .pagination .page-item.active .page-link {
            background: var(--gl-blue);
            border-color: var(--gl-blue);
            color: #fff;
        }

        /* ==========================================================
           Mobile (≤767px): tabel jadi kartu bertumpuk
           ========================================================== */
        @media (max-width: 767.98px) {
            .citizens-page .card-body {
                padding: 1rem !important;
            }
            .citizens-page .table-responsive {
                overflow-x: visible;
                border: none;
                box-shadow: none;
                background: transparent;
            }
            .citizens-page .table,
            .citizens-page .table thead,
            .citizens-page .table tbody,
            .citizens-page .table tr,
            .citizens-page .table td {
                display: block;
                width: 100%;
            }
            .citizens-page .table thead {
                display: none;
            }
            .citizens-page .table tr {
                background: rgba(255, 255, 255, 0.55);
                backdrop-filter: blur(14px) saturate(180%);
                -webkit-backdrop-filter: blur(14px) saturate(180%);
                border: 1px solid rgba(255, 255, 255, 0.65);
                border-radius: 16px;
                box-shadow: 0 6px 20px rgba(31, 60, 136, 0.1);
                padding: .85rem 1rem;
                margin-bottom: .75rem;
            }
            .citizens-page .table td {
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
            .citizens-page .table td:last-child {
                border-bottom: none !important;
            }
            .citizens-page .table td::before {
                content: attr(data-label);
                font-weight: 700;
                font-size: .68rem;
                text-transform: uppercase;
                letter-spacing: .04em;
                color: var(--gl-blue);
                text-align: left;
                flex-shrink: 0;
            }
            .citizens-page .table td[data-label="Aksi"] {
                justify-content: flex-end;
            }
            .citizens-page .table td[data-label="Aksi"]::before {
                display: none;
            }
            .citizens-page .table tr:has(td[colspan]) {
                background: transparent;
                border: none;
                box-shadow: none;
                padding: 0;
            }
            .citizens-page .table tr:has(td[colspan]) td {
                display: block;
                text-align: center;
            }
        }
    </style>
@endpush

@section('content')
<div class="citizens-page">
    <span class="glass-blob glass-blob--1" aria-hidden="true"></span>
    <span class="glass-blob glass-blob--2" aria-hidden="true"></span>
    <span class="glass-blob glass-blob--3" aria-hidden="true"></span>

    <main id="main-content" class="container app-main py-4">
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route($routePrefix.'.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">Warga</li></ol></nav>
        <header class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div class="d-flex gap-3"><span class="page-icon"><i class="bi bi-people"></i></span><div><p class="section-eyebrow mb-1">Master kependudukan</p><h1 class="h2 section-title mb-1">Data Warga</h1><p class="text-secondary mb-0">Daftar penduduk sesuai wilayah akses Anda.</p></div></div>@can('create', \App\Models\Citizen::class)<a class="btn btn-primary" href="{{ route($routePrefix.'.citizens.create') }}"><i class="bi bi-person-plus me-1"></i>Tambah Warga</a>@endcan</header>
        @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
        <form class="card card-body border-0 shadow-sm mb-3"><div class="row g-2"><div class="col-md"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Cari nama, NIK, atau telepon" aria-label="Cari warga"></div><div class="col-md-auto"><select class="form-select" name="status" aria-label="Filter status"><option value="">Semua status</option><option value="active" @selected(request('status') === 'active')>Aktif</option><option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option></select></div>@if($routePrefix === 'kelurahan')<div class="col-md-auto"><select class="form-select" name="rw_id" aria-label="Filter RW"><option value="">Semua RW</option>@foreach($rts->pluck('rw')->unique('id') as $rw)<option value="{{ $rw->id }}" @selected((int) request('rw_id') === $rw->id)>RW {{ $rw->code }}</option>@endforeach</select></div>@endif @if($routePrefix !== 'rt')<div class="col-md-auto"><select class="form-select" name="rt_id" aria-label="Filter RT"><option value="">Semua RT</option>@foreach($rts as $rt)<option value="{{ $rt->id }}" @selected((int) request('rt_id') === $rt->id)>RW {{ $rt->rw->code }} / RT {{ $rt->code }}</option>@endforeach</select></div>@endif<div class="col-md-auto"><button class="btn btn-outline-primary">Filter</button></div></div></form>
        <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Nama</th><th>NIK</th><th>Nomor KK</th><th>Hubungan Keluarga</th>@if($routePrefix !== 'rt')<th>Wilayah</th>@endif<th>Status</th><th>Aksi</th></tr></thead><tbody>@forelse($citizens as $citizen)@php($url=route($routePrefix.'.citizens.show', $citizen))<tr tabindex="0" data-row-url="{{ $url }}"><td data-label="Nama"><a class="fw-semibold" href="{{ $url }}">{{ $citizen->name }}</a></td><td data-label="NIK">{{ $citizen->nik ?: '—' }}</td><td data-label="Nomor KK">{{ $citizen->familyCard?->family_number ?: 'Belum ada' }}</td><td data-label="Hubungan Keluarga">{{ $citizen->family_relationship?->label() ?: 'Belum ditentukan' }}</td>@if($routePrefix !== 'rt')<td data-label="Wilayah">RW {{ $citizen->rt->rw->code }} / RT {{ $citizen->rt->code }}</td>@endif<td data-label="Status"><span class="badge text-bg-{{ $citizen->is_active ? 'success' : 'secondary' }}">{{ $citizen->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td data-label="Aksi"><a class="btn btn-outline-secondary btn-sm" href="{{ $url }}">Detail</a></td></tr>@empty<tr><td colspan="{{ $routePrefix !== 'rt' ? 7 : 6 }}" class="text-center text-secondary py-5">Belum ada data warga yang sesuai dengan pencarian atau filter.</td></tr>@endforelse</tbody></table></div></div>
        <div class="mt-3">{{ $citizens->links('pagination::bootstrap-5') }}</div>
    </main>
</div>
@endsection