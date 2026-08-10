@extends('layouts.app')
@section('title','Kelola RT - SIGAP WARGA')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">
    <style>
        /* ==========================================================
           SIGAP WARGA — RW Pages: Glassmorphism (Blue Accent)
           Sama persis dengan dashboard RW
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

        .rw-dashboard > main { position: relative; z-index: 1; }

        /* ---------- Breadcrumb ---------- */
        .rw-dashboard .breadcrumb-item a {
            color: var(--gl-blue);
            font-weight: 600;
            text-decoration: none;
        }
        .rw-dashboard .breadcrumb-item.active {
            color: var(--gl-muted);
        }

        /* ---------- Cards ---------- */
        .rw-dashboard .card {
            background: var(--gl-glass-bg) !important;
            backdrop-filter: blur(18px) saturate(180%);
            -webkit-backdrop-filter: blur(18px) saturate(180%);
            border: 1px solid var(--gl-glass-border) !important;
            border-radius: 20px !important;
            box-shadow: var(--gl-glass-shadow) !important;
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
        .rw-dashboard .btn-outline-secondary {
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(43, 92, 255, 0.25);
            color: var(--gl-ink);
            backdrop-filter: blur(6px);
        }
        .rw-dashboard .btn-outline-secondary:hover {
            background: rgba(255, 255, 255, 0.9);
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

        /* ---------- Tables ---------- */
        .rw-dashboard .table-responsive {
            border-radius: 20px;
            overflow: hidden;
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
            padding: .9rem;
            font-weight: 500;
        }
        .rw-dashboard .table-hover tbody tr:hover {
            background-color: rgba(43, 92, 255, 0.06);
        }
        .rw-dashboard [data-row-url] {
            cursor: pointer;
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
<div class="rw-dashboard">
    <span class="glass-blob glass-blob--1" aria-hidden="true"></span>
    <span class="glass-blob glass-blob--2" aria-hidden="true"></span>
    <span class="glass-blob glass-blob--3" aria-hidden="true"></span>

    <main class="container py-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('rw.dashboard') }}">Dashboard RW</a></li>
                <li class="breadcrumb-item active">Kelola RT</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between mb-4">
            <div>
                <h1 class="h2 fw-bold">Kelola RT</h1>
                <p class="text-secondary">Kelola RT di wilayah RW Anda.</p>
            </div>
            <a class="btn btn-primary align-self-start" href="{{ route('rw.rts.create') }}">Tambah RT</a>
        </div>

        <div class="card border-0 mb-4">
            <div class="card-body">
                <form class="row g-3">
                    <div class="col-md-6">
                        <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Cari kode atau nama RT">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">Semua status</option>
                            <option value="active" @selected(request('status')==='active')>Aktif</option>
                            <option value="inactive" @selected(request('status')==='inactive')>Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary">Cari</button>
                        <a class="btn btn-outline-secondary" href="{{ route('rw.rts.index') }}">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Kode</th>
                            <th>Nama</th>
                            <th>WhatsApp</th>
                            <th>Warga</th>
                            <th>Laporan</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rts as $rt)
                            <tr tabindex="0" data-row-url="{{ route('rw.rts.edit',$rt) }}">
                                <td class="ps-4">{{ $rt->code }}</td>
                                <td>{{ $rt->name }}</td>
                                <td>{{ $rt->whatsapp_number ?? '—' }}</td>
                                <td>{{ $rt->citizens_count }}</td>
                                <td>{{ $rt->reports_count }}</td>
                                <td><span class="badge text-bg-{{ $rt->is_active ? 'success' : 'secondary' }}">{{ $rt->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td><a class="btn btn-outline-primary btn-sm" href="{{ route('rw.rts.edit',$rt) }}">Edit</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-5 text-secondary">Belum ada RT atau data tidak sesuai pencarian.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($rts->hasPages())
                <div class="p-3 border-top">{{ $rts->links('pagination::bootstrap-5') }}</div>
            @endif
        </div>
    </main>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-row-url]').forEach((row) => {
    const go = () => window.location.assign(row.dataset.rowUrl);
    row.addEventListener('click', (e) => {
        if (!(e.target instanceof Element && e.target.closest('a,button,input,select')) && e.button === 0) go();
    });
    row.addEventListener('keydown', (e) => {
        if (e.target === row && e.key === 'Enter') { e.preventDefault(); go(); }
    });
});
</script>
@endpush