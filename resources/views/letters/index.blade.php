@extends('layouts.app')
@section('title', 'Administrasi Surat')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">
    <style>
        /* ==========================================================
           SIGAP WARGA — Halaman Surat: Glassmorphism (Blue Accent)
           Berlaku untuk semua role: RT, RW, Kelurahan
           ========================================================== */
        .glass-letters,
        .glass-letters *,
        .glass-letters ::placeholder {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important;
        }

        .glass-letters {
            --gl-blue: #2b5cff;
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
            padding-bottom: 2rem;
        }

        .glass-letters .glass-blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            z-index: 0;
            pointer-events: none;
        }
        .glass-letters .glass-blob--1 { width: 440px; height: 440px; top: -10%; left: -8%; background: radial-gradient(circle, rgba(43,92,255,0.5), transparent 70%); }
        .glass-letters .glass-blob--2 { width: 400px; height: 400px; top: 22%; right: -12%; background: radial-gradient(circle, rgba(124,92,255,0.4), transparent 70%); }
        .glass-letters .glass-blob--3 { width: 480px; height: 480px; bottom: -15%; left: 20%; background: radial-gradient(circle, rgba(52,209,200,0.35), transparent 70%); }

        .glass-letters > .app-main { position: relative; z-index: 1; }

        .glass-letters .breadcrumb-item a { color: var(--gl-blue); font-weight: 600; text-decoration: none; }
        .glass-letters .breadcrumb-item.active { color: var(--gl-muted); }

        .glass-letters .page-icon,
        .glass-letters .icon-box {
            background: linear-gradient(135deg, var(--gl-blue), var(--gl-blue-light)) !important;
            color: #fff !important;
            border-radius: 14px !important;
            box-shadow: 0 6px 16px rgba(43, 92, 255, 0.3);
        }

        .glass-letters .section-eyebrow { color: var(--gl-blue) !important; font-weight: 800; }

        /* Filter chip buttons (Hari Ini / Diproses / Terbit / Ditolak) */
        .glass-letters .btn-outline-primary,
        .glass-letters .btn-outline-warning,
        .glass-letters .btn-outline-success,
        .glass-letters .btn-outline-danger {
            background: rgba(255, 255, 255, 0.6) !important;
            backdrop-filter: blur(6px);
            border-radius: 50rem !important;
            font-weight: 600;
        }
        .glass-letters .btn-primary,
        .glass-letters .btn-warning,
        .glass-letters .btn-success,
        .glass-letters .btn-danger {
            border-radius: 50rem !important;
            font-weight: 700;
            border: none !important;
        }
        .glass-letters .btn-primary { background: linear-gradient(135deg, var(--gl-blue), var(--gl-blue-light)) !important; box-shadow: 0 8px 22px rgba(43, 92, 255, 0.35); }
        .glass-letters .btn-warning { background: linear-gradient(135deg, #ffd60a, #ff9f1c) !important; color: #1c2340 !important; }
        .glass-letters .btn-success { background: linear-gradient(135deg, #34d399, #10b981) !important; }
        .glass-letters .btn-danger  { background: linear-gradient(135deg, #f87171, #ef4444) !important; }

        .glass-letters .btn {
            border-radius: 12px;
            font-weight: 700;
        }
        .glass-letters .btn-outline-secondary {
            background: rgba(255, 255, 255, 0.6) !important;
            border: 1px solid rgba(43, 92, 255, 0.25) !important;
            backdrop-filter: blur(6px);
        }

        /* Cards */
        .glass-letters .filter-panel,
        .glass-letters .card {
            background: var(--gl-glass-bg) !important;
            backdrop-filter: blur(18px) saturate(180%);
            -webkit-backdrop-filter: blur(18px) saturate(180%);
            border: 1px solid var(--gl-glass-border) !important;
            border-radius: 20px !important;
            box-shadow: var(--gl-glass-shadow) !important;
        }

        /* Forms */
        .glass-letters .form-label { font-weight: 700; color: var(--gl-ink); }
        .glass-letters .form-select,
        .glass-letters .form-control,
        .glass-letters .input-group-text {
            background: rgba(255, 255, 255, 0.6) !important;
            border: 1px solid rgba(43, 92, 255, 0.25) !important;
            border-radius: 12px !important;
            backdrop-filter: blur(6px);
            font-weight: 500;
        }
        .glass-letters .form-select:focus,
        .glass-letters .form-control:focus {
            border-color: var(--gl-blue) !important;
            box-shadow: 0 0 0 3px rgba(43, 92, 255, 0.15) !important;
            background: rgba(255, 255, 255, 0.85) !important;
        }
        .glass-letters .input-group .form-control { border-left: none !important; }
        .glass-letters .input-group .input-group-text { border-right: none !important; }

        /* Badge */
        .glass-letters .badge {
            font-weight: 700;
            backdrop-filter: blur(6px);
            border-radius: 50rem;
        }
        .glass-letters .badge-status { padding: .45em .8em; }

        /* Table */
        .glass-letters .table-responsive { border-radius: 20px; overflow: hidden; }
        .glass-letters table.table { margin-bottom: 0; }
        .glass-letters table.table thead th {
            background: rgba(43, 92, 255, 0.08) !important;
            color: var(--gl-blue);
            text-transform: uppercase;
            font-size: .72rem;
            letter-spacing: .06em;
            font-weight: 700;
            border-bottom: 1px solid rgba(43, 92, 255, 0.15);
        }
        .glass-letters table.table tbody td {
            border-bottom: 1px solid rgba(43, 92, 255, 0.08);
            font-weight: 500;
        }
        .glass-letters .table-hover tbody tr:hover,
        .glass-letters [data-row-url]:hover { background-color: rgba(43, 92, 255, 0.06); }
        .glass-letters [data-row-url] { cursor: pointer; }

        /* Empty state */
        .glass-letters .empty-state-icon {
            background: rgba(43, 92, 255, 0.12) !important;
            color: var(--gl-blue) !important;
        }

        .glass-letters .card-footer { background: transparent !important; border-top: 1px solid rgba(43, 92, 255, 0.12) !important; }
        .glass-letters .pagination .page-link {
            border: 1px solid rgba(43, 92, 255, 0.25);
            border-radius: 10px;
            color: var(--gl-blue);
            font-weight: 600;
            background: rgba(255, 255, 255, 0.6);
        }
        .glass-letters .pagination .page-item.active .page-link {
            background: var(--gl-blue);
            border-color: var(--gl-blue);
            color: #fff;
        }

        /* ---------- Mobile friendly ---------- */
        .glass-letters { overflow-x: hidden; }

        @media (max-width: 767.98px) {
            .glass-letters .glass-blob--1,
            .glass-letters .glass-blob--2,
            .glass-letters .glass-blob--3 {
                width: 260px;
                height: 260px;
                filter: blur(60px);
            }

            .glass-letters .page-header {
                flex-direction: column;
                align-items: stretch !important;
                gap: 1rem !important;
            }
            .glass-letters .page-header .btn {
                width: 100%;
                justify-content: center;
                display: flex;
                align-items: center;
            }
            .glass-letters .page-header h1 {
                font-size: 1.4rem;
            }
            .glass-letters .page-icon,
            .glass-letters .icon-box {
                width: 2.25rem;
                height: 2.25rem;
                flex: 0 0 2.25rem;
                font-size: .9rem;
            }

            /* Filter chip: scroll horizontal biar nggak wrap acak */
            .glass-letters .filter-chip-row {
                flex-wrap: nowrap !important;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                padding-bottom: .35rem;
                scrollbar-width: none;
            }
            .glass-letters .filter-chip-row::-webkit-scrollbar { display: none; }
            .glass-letters .filter-chip-row .btn {
                flex: 0 0 auto;
                white-space: nowrap;
            }

            .glass-letters .filter-panel { padding: 1rem !important; border-radius: 16px !important; }
            .glass-letters .card-header {
                flex-direction: column;
                align-items: flex-start !important;
                gap: .5rem;
            }

            .glass-letters .table-responsive { border-radius: 16px; }
            .glass-letters table.table thead th,
            .glass-letters table.table tbody td {
                padding: .65rem .75rem;
                font-size: .85rem;
                white-space: nowrap;
            }
            .glass-letters table.table tbody td.td-empty {
                white-space: normal;
            }
            .glass-letters .empty-state {
                white-space: normal;
                padding: 2rem 1rem;
            }
            .glass-letters .empty-state h3 {
                font-size: 1.1rem;
            }
            .glass-letters .empty-state p {
                font-size: .85rem;
            }
        }

        @media (max-width: 575.98px) {
            .glass-letters .display-6,
            .glass-letters h1.h2 { font-size: 1.25rem; }
            .glass-letters .card-body,
            .glass-letters .card-header { padding: 1rem !important; }
        }
    </style>
@endpush

@section('content')
<main id="main-content" class="container app-main glass-letters">
    <span class="glass-blob glass-blob--1" aria-hidden="true"></span>
    <span class="glass-blob glass-blob--2" aria-hidden="true"></span>
    <span class="glass-blob glass-blob--3" aria-hidden="true"></span>

    <nav aria-label="Breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route($routePrefix.'.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active" aria-current="page">Surat</li></ol></nav>
    <header class="page-header d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div class="d-flex align-items-start gap-3"><span class="page-icon" aria-hidden="true"><i class="bi bi-envelope-paper"></i></span><div><p class="section-eyebrow mb-1">Pelayanan administrasi</p><h1 class="h2 section-title mb-1">{{ $routePrefix === 'rt' ? 'Pengajuan Surat' : ($routePrefix === 'rw' ? 'Verifikasi Surat' : 'Administrasi Surat') }}</h1><p class="text-secondary mb-0">Kelola alur surat warga sesuai kewenangan Anda.</p></div></div>
        @can('create', \App\Models\VillageLetter::class)<a class="btn btn-primary" href="{{ route('rt.letters.create') }}"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Buat Pengajuan</a>@endcan
    </header>

    <div class="d-flex flex-wrap gap-2 mb-3 filter-chip-row" aria-label="Filter cepat surat">
        <a class="btn btn-sm {{ request('date_from') === now()->toDateString() ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route($routePrefix.'.letters.index', ['date_from' => now()->toDateString(), 'date_to' => now()->toDateString()]) }}"><i class="bi bi-calendar-day me-1"></i>Hari Ini</a>
        <a class="btn btn-sm {{ request('status') === 'RW_REVIEWED' ? 'btn-warning' : 'btn-outline-warning' }}" href="{{ route($routePrefix.'.letters.index', ['status' => 'RW_REVIEWED']) }}"><i class="bi bi-hourglass-split me-1"></i>Diproses</a>
        <a class="btn btn-sm {{ request('status') === 'ISSUED' ? 'btn-success' : 'btn-outline-success' }}" href="{{ route($routePrefix.'.letters.index', ['status' => 'ISSUED']) }}"><i class="bi bi-check-circle me-1"></i>Terbit</a>
        <a class="btn btn-sm {{ request('status') === 'REJECTED' ? 'btn-danger' : 'btn-outline-danger' }}" href="{{ route($routePrefix.'.letters.index', ['status' => 'REJECTED']) }}"><i class="bi bi-x-circle me-1"></i>Ditolak</a>
    </div>

    <form class="filter-panel p-3 p-lg-4 mb-4" method="GET" aria-labelledby="filter-title">
        <h2 id="filter-title" class="h6 section-title mb-3"><i class="bi bi-funnel me-2" aria-hidden="true"></i>Filter Surat</h2>
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg"><label class="form-label" for="letter-search">Pencarian</label><div class="input-group"><span class="input-group-text"><i class="bi bi-search"></i></span><input id="letter-search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Nama, NIK, atau nomor surat"></div></div>
            <div class="col-sm-6 col-lg-auto"><label class="form-label" for="letter-type">Jenis</label><select id="letter-type" name="type" class="form-select"><option value="">Semua jenis</option>@foreach(\App\Enums\LetterType::cases() as $type)<option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>@endforeach</select></div>
            <div class="col-sm-6 col-lg-auto"><label class="form-label" for="letter-status">Status</label><select id="letter-status" name="status" class="form-select"><option value="">Semua status</option>@foreach(\App\Enums\LetterStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
            @if($routePrefix === 'kelurahan')<div class="col-sm-6 col-lg-auto"><label class="form-label" for="rw-filter">RW</label><select id="rw-filter" name="rw_id" class="form-select"><option value="">Semua RW</option>@foreach($rts->pluck('rw')->unique('id') as $rw)<option value="{{ $rw->id }}" @selected((int) request('rw_id') === $rw->id)>RW {{ $rw->code }}</option>@endforeach</select></div>@endif
            @if($routePrefix !== 'rt')<div class="col-sm-6 col-lg-auto"><label class="form-label" for="rt-filter">RT</label><select id="rt-filter" name="rt_id" class="form-select"><option value="">Semua RT</option>@foreach($rts as $rt)<option value="{{ $rt->id }}" @selected((int) request('rt_id') === $rt->id)>RW {{ $rt->rw->code }}/RT {{ $rt->code }}</option>@endforeach</select></div>@endif
            @if($routePrefix === 'kelurahan')<div class="col-sm-6 col-lg-auto"><label class="form-label" for="date-from">Dari</label><input id="date-from" type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div><div class="col-sm-6 col-lg-auto"><label class="form-label" for="date-to">Sampai</label><input id="date-to" type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>@endif
            <div class="col-12 col-lg-auto d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Terapkan</button><a class="btn btn-outline-secondary" href="{{ route($routePrefix.'.letters.index') }}" aria-label="Reset filter"><i class="bi bi-arrow-counterclockwise"></i></a></div>
        </div>
    </form>

    <section class="card border-0" aria-labelledby="letter-list-title"><div class="card-header bg-white p-4 d-flex justify-content-between align-items-center"><h2 id="letter-list-title" class="h5 section-title mb-0">Daftar Surat</h2><span class="badge text-bg-primary badge-status">{{ number_format($letters->total()) }} data</span></div><div class="table-responsive"><table class="table table-hover table-sticky align-middle mb-0"><thead><tr><th>Nomor</th><th>Warga</th><th>Jenis</th><th>Wilayah</th><th>Status</th><th>Tanggal</th><th><span class="visually-hidden">Aksi</span></th></tr></thead><tbody>
        @forelse($letters as $letter)@php($url = route($routePrefix.'.letters.show', $letter))<tr data-row-url="{{ $url }}" aria-label="Buka detail surat {{ $letter->citizen->name }}"><td class="fw-semibold">{{ $letter->letter_number ?: 'Belum terbit' }}</td><td><a class="fw-semibold" href="{{ $url }}">{{ $letter->citizen->name }}</a><small class="d-block text-secondary">{{ $letter->citizen->nik ?: 'NIK belum diisi' }}</small></td><td>{{ $letter->letter_type->label() }}</td><td>RW {{ $letter->rt->rw->code }}/RT {{ $letter->rt->code }}</td><td><span class="badge badge-status text-bg-{{ $letter->status->bootstrapColor() }}">{{ $letter->status->label() }}</span></td><td>{{ $letter->created_at->locale('id')->isoFormat('D MMM Y') }}</td><td><a class="btn btn-outline-primary btn-sm" href="{{ $url }}" aria-label="Detail surat {{ $letter->citizen->name }}"><i class="bi bi-chevron-right"></i></a></td></tr>
        @empty<tr><td colspan="7" class="td-empty"><div class="empty-state"><span class="empty-state-icon"><i class="bi bi-inbox"></i></span><h3 class="h5">Belum ada pengajuan surat</h3><p class="text-secondary">Belum ada data yang cocok dengan pencarian atau filter yang dipilih.</p>@can('create', \App\Models\VillageLetter::class)<a class="btn btn-primary" href="{{ route('rt.letters.create') }}"><i class="bi bi-plus-lg me-1"></i>Buat Pengajuan</a>@else<a class="btn btn-outline-primary" href="{{ route($routePrefix.'.letters.index') }}">Reset Filter</a>@endcan</div></td></tr>@endforelse
    </tbody></table></div>@if($letters->hasPages())<div class="card-footer bg-white p-3 d-flex justify-content-center">{{ $letters->links('pagination::bootstrap-5') }}</div>@endif</section>
</main>
@endsection