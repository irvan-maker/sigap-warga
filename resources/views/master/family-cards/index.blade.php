@extends('layouts.app')
@section('title', 'Kartu Keluarga')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ==========================================================
           SIGAP WARGA — Kartu Keluarga: Glassmorphism (blue accent)
           Sama persis dengan sistem desain Dashboard RT / Kelurahan
           ========================================================== */
        .family-cards-page {
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

        .family-cards-page h1,
        .family-cards-page h2 {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            font-weight: 800;
        }

        /* floating blurred color blobs = the "glass" needs something to refract */
        .family-cards-page .glass-blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            z-index: 0;
            pointer-events: none;
        }
        .family-cards-page .glass-blob--1 {
            width: 420px; height: 420px;
            top: -12%; left: -10%;
            background: radial-gradient(circle, rgba(43,92,255,0.5), transparent 70%);
        }
        .family-cards-page .glass-blob--2 {
            width: 380px; height: 380px;
            top: 25%; right: -14%;
            background: radial-gradient(circle, rgba(124,92,255,0.4), transparent 70%);
        }
        .family-cards-page .glass-blob--3 {
            width: 420px; height: 420px;
            bottom: -16%; left: 20%;
            background: radial-gradient(circle, rgba(52,209,200,0.35), transparent 70%);
        }

        .family-cards-page main.app-main {
            position: relative;
            z-index: 1;
        }

        /* ---------- Breadcrumb ---------- */
        .family-cards-page .breadcrumb-item a {
            color: var(--gl-blue);
            font-weight: 600;
            text-decoration: none;
        }
        .family-cards-page .breadcrumb-item a:hover { text-decoration: underline; }
        .family-cards-page .breadcrumb-item.active { color: var(--gl-muted); }

        /* ---------- Alert ---------- */
        .family-cards-page .alert-success {
            background: rgba(20, 184, 166, 0.12);
            border: 1px solid rgba(20, 184, 166, 0.3);
            color: #0f7a6d;
            border-radius: 14px;
            backdrop-filter: blur(6px);
            font-weight: 600;
        }

        /* ---------- Glass cards ---------- */
        .family-cards-page .card {
            background: var(--gl-glass-bg) !important;
            backdrop-filter: blur(18px) saturate(180%);
            -webkit-backdrop-filter: blur(18px) saturate(180%);
            border: 1px solid var(--gl-glass-border) !important;
            border-radius: 20px !important;
            box-shadow: var(--gl-glass-shadow) !important;
        }

        /* ---------- Forms ---------- */
        .family-cards-page .form-select,
        .family-cards-page .form-control {
            background: rgba(255, 255, 255, 0.6) !important;
            border: 1px solid rgba(43, 92, 255, 0.25) !important;
            border-radius: 12px !important;
            backdrop-filter: blur(6px);
            font-weight: 500;
        }
        .family-cards-page .form-select:focus,
        .family-cards-page .form-control:focus {
            border-color: var(--gl-blue) !important;
            box-shadow: 0 0 0 3px rgba(43, 92, 255, 0.15) !important;
            background: rgba(255, 255, 255, 0.85) !important;
        }

        /* ---------- Buttons ---------- */
        .family-cards-page .btn {
            border-radius: 12px;
            font-weight: 700;
        }
        .family-cards-page .btn-primary {
            background: linear-gradient(135deg, var(--gl-blue), var(--gl-blue-light));
            border: none;
            box-shadow: 0 8px 22px rgba(43, 92, 255, 0.35);
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .family-cards-page .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(43, 92, 255, 0.45);
        }
        .family-cards-page .btn-outline-primary {
            background: rgba(43, 92, 255, 0.08);
            border: 1px solid rgba(43, 92, 255, 0.4);
            color: var(--gl-blue);
            backdrop-filter: blur(6px);
        }
        .family-cards-page .btn-outline-primary:hover {
            background: var(--gl-blue);
            border-color: var(--gl-blue);
            color: #fff;
        }
        .family-cards-page .btn-outline-secondary {
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(28, 35, 64, 0.2);
            color: var(--gl-ink);
            backdrop-filter: blur(6px);
        }
        .family-cards-page .btn-outline-secondary:hover {
            background: rgba(255, 255, 255, 0.8);
        }

        /* ---------- Badges ---------- */
        .family-cards-page .badge {
            font-weight: 700;
            backdrop-filter: blur(6px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        /* ---------- Table ---------- */
        .family-cards-page .table-responsive {
            border-radius: 16px;
            overflow: hidden;
        }
        .family-cards-page table.table thead th {
            background: rgba(43, 92, 255, 0.08);
            color: var(--gl-blue);
            text-transform: uppercase;
            font-size: .72rem;
            letter-spacing: .06em;
            font-weight: 700;
            border-bottom: 1px solid rgba(43, 92, 255, 0.15);
            padding: .9rem .85rem;
        }
        .family-cards-page table.table tbody td {
            border-bottom: 1px solid rgba(43, 92, 255, 0.08);
            padding: .8rem .85rem;
            font-weight: 500;
        }
        .family-cards-page .table-hover tbody tr:hover {
            background-color: rgba(43, 92, 255, 0.06);
            cursor: pointer;
        }

        /* ---------- Pagination ---------- */
        .family-cards-page .pagination .page-link {
            border: 1px solid rgba(43, 92, 255, 0.25);
            border-radius: 10px;
            margin-inline: 3px;
            font-weight: 600;
            color: var(--gl-blue);
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(6px);
        }
        .family-cards-page .pagination .page-item.active .page-link {
            background: var(--gl-blue);
            border-color: var(--gl-blue);
            color: #fff;
        }

        /* ==========================================================
           Mobile (≤767px): tabel jadi kartu bertumpuk
           ========================================================== */
        @media (max-width: 767.98px) {
            .family-cards-page .card-body {
                padding: 1rem !important;
            }
            .family-cards-page .table-responsive {
                overflow-x: visible;
                border: none;
                box-shadow: none;
                background: transparent;
            }
            .family-cards-page .table,
            .family-cards-page .table thead,
            .family-cards-page .table tbody,
            .family-cards-page .table tr,
            .family-cards-page .table td {
                display: block;
                width: 100%;
            }
            .family-cards-page .table thead {
                display: none;
            }
            .family-cards-page .table tr {
                background: rgba(255, 255, 255, 0.55);
                backdrop-filter: blur(14px) saturate(180%);
                -webkit-backdrop-filter: blur(14px) saturate(180%);
                border: 1px solid rgba(255, 255, 255, 0.65);
                border-radius: 16px;
                box-shadow: 0 6px 20px rgba(31, 60, 136, 0.1);
                padding: .85rem 1rem;
                margin-bottom: .75rem;
            }
            .family-cards-page .table td {
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
            .family-cards-page .table td:last-child {
                border-bottom: none !important;
            }
            .family-cards-page .table td::before {
                content: attr(data-label);
                font-weight: 700;
                font-size: .68rem;
                text-transform: uppercase;
                letter-spacing: .04em;
                color: var(--gl-blue);
                text-align: left;
                flex-shrink: 0;
            }
            .family-cards-page .table td[data-label="Nomor KK"] {
                align-items: flex-start;
            }
            .family-cards-page .table td[data-label="Aksi"] {
                justify-content: flex-end;
            }
            .family-cards-page .table td[data-label="Aksi"]::before {
                display: none;
            }
            .family-cards-page .table tr:has(td[colspan]) {
                background: transparent;
                border: none;
                box-shadow: none;
                padding: 0;
            }
            .family-cards-page .table tr:has(td[colspan]) td {
                display: block;
                text-align: center;
            }
        }
    </style>
@endpush

@section('content')
<div class="family-cards-page">
    <span class="glass-blob glass-blob--1" aria-hidden="true"></span>
    <span class="glass-blob glass-blob--2" aria-hidden="true"></span>
    <span class="glass-blob glass-blob--3" aria-hidden="true"></span>

    <main id="main-content" class="container app-main py-4">
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route($routePrefix.'.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">Kartu Keluarga</li></ol></nav>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3"><div><h1 class="h3 mb-1">Kartu Keluarga</h1><p class="text-secondary mb-0">Administrasi keluarga sesuai wilayah akses Anda.</p></div>@can('create', \App\Models\FamilyCard::class)<a class="btn btn-primary" href="{{ route($routePrefix.'.family-cards.create') }}">Tambah Kartu Keluarga</a>@endcan</div>
        @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
        <form class="card card-body border-0 shadow-sm mb-3"><div class="row g-2"><div class="col-md"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Cari nomor KK atau kepala keluarga" aria-label="Cari Kartu Keluarga"></div><div class="col-md-auto"><select class="form-select" name="status" aria-label="Filter status"><option value="">Semua status</option><option value="active" @selected(request('status') === 'active')>Aktif</option><option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option></select></div>@if($routePrefix === 'kelurahan')<div class="col-md-auto"><select class="form-select" name="rw_id" aria-label="Filter RW"><option value="">Semua RW</option>@foreach($rts->pluck('rw')->unique('id') as $rw)<option value="{{ $rw->id }}" @selected((int) request('rw_id') === $rw->id)>RW {{ $rw->code }}</option>@endforeach</select></div>@endif @if($routePrefix !== 'rt')<div class="col-md-auto"><select class="form-select" name="rt_id" aria-label="Filter RT"><option value="">Semua RT</option>@foreach($rts as $rt)<option value="{{ $rt->id }}" @selected((int) request('rt_id') === $rt->id)>RW {{ $rt->rw->code }} / RT {{ $rt->code }}</option>@endforeach</select></div>@endif<div class="col-md-auto"><button class="btn btn-outline-primary">Filter</button></div></div></form>
        <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Nomor KK</th><th>Kepala Keluarga</th><th>Alamat</th><th>Anggota</th><th>Kelengkapan</th><th>Status</th><th>Aksi</th></tr></thead><tbody>@forelse($familyCards as $card)@php($url=route($routePrefix.'.family-cards.show', $card))<tr tabindex="0" data-row-url="{{ $url }}"><td data-label="Nomor KK"><a class="fw-semibold" href="{{ $url }}">{{ $card->family_number }}</a><div class="small text-secondary">RW {{ $card->rt->rw->code }} / RT {{ $card->rt->code }}</div></td><td data-label="Kepala Keluarga">{{ $card->headCitizen?->name ?: 'Belum ditentukan' }}</td><td data-label="Alamat"><span title="{{ $card->address }}">{{ \Illuminate\Support\Str::limit($card->address ?: 'Belum diisi', 45) }}</span></td><td data-label="Anggota">{{ $card->citizens_count }} orang</td><td data-label="Kelengkapan">@if(!$card->head_citizen_id)<span class="badge text-bg-warning">Belum ada kepala keluarga</span>@elseif($card->citizens_without_nik_count > 0)<span class="badge text-bg-warning">Ada anggota tanpa NIK</span>@else<span class="badge text-bg-success">Lengkap</span>@endif</td><td data-label="Status"><span class="badge text-bg-{{ $card->is_active ? 'success' : 'secondary' }}">{{ $card->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td data-label="Aksi"><a class="btn btn-outline-secondary btn-sm" href="{{ $url }}">Detail</a></td></tr>@empty<tr><td colspan="7" class="text-center text-secondary py-5">Belum ada Kartu Keluarga yang sesuai dengan pencarian atau filter.</td></tr>@endforelse</tbody></table></div></div>
        <div class="mt-3">{{ $familyCards->links('pagination::bootstrap-5') }}</div>
    </main>
</div>
@endsection