@extends('layouts.app')

@section('title', 'Dashboard Kelurahan - SIGAP WARGA')

@section('content')
    @php
        $officer = auth()->user();
        $reportDetailRoute = $officer->isSystemAdmin() ? 'reports.show' : 'kelurahan.reports.show';
        $canManageRws = $officer->isSystemAdmin() || $officer->isVillageSecretary();
    @endphp

    <div class="kelurahan-dashboard min-vh-100">
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
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-4">
                    <div>
                        <span class="badge rounded-pill bg-white bg-opacity-10 border border-white border-opacity-25 px-3 py-2 mb-3">Layanan aktif</span>
                        <p class="text-white-50 mb-1">{{ config('village.name') }}</p>
                        <h1 class="h2 fw-bold mb-2">Selamat datang, {{ $officer->name }}</h1>
                        <p class="mb-0 text-white-75">{{ $officer->position?->label() }} · Pusat pemantauan laporan warga dan operasional wilayah desa.</p>
                    </div>
                    <div class="hero-meta rounded-3 p-3">
                        <span class="small text-white-50 d-block">Hari ini</span>
                        <strong>{{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}</strong>
                    </div>
                </div>
            </header>

            <section class="mb-4" aria-labelledby="kpi-heading">
                <div class="mb-3">
                    <p class="section-eyebrow mb-1">Layanan warga</p>
                    <h2 id="kpi-heading" class="h4 fw-bold mb-0">Ringkasan Laporan</h2>
                </div>
                <div class="row g-3">
                    <div class="col-6 col-lg"><a class="card navigation-card h-100 border-0 shadow-sm text-decoration-none" href="{{ route('kelurahan.citizens.index') }}"><span class="card-body p-3"><span class="text-secondary small d-block">Warga Aktif</span><strong class="fs-3 text-body">{{ number_format($activeCitizenCount) }}</strong></span></a></div>
                    <div class="col-6 col-lg"><a class="card navigation-card h-100 border-0 shadow-sm text-decoration-none" href="{{ route('kelurahan.family-cards.index') }}"><span class="card-body p-3"><span class="text-secondary small d-block">KK Aktif</span><strong class="fs-3 text-body">{{ number_format($activeFamilyCardCount) }}</strong></span></a></div>
                    <div class="col-6 col-lg">
                        <a class="card navigation-card h-100 border-0 shadow-sm text-decoration-none" href="{{ route('kelurahan.reports.index') }}#laporan">
                            <span class="card-body p-3 p-lg-4">
                                <span class="text-secondary small d-block">Total Laporan</span>
                                <strong class="fs-3 text-body d-block">{{ number_format($total) }}</strong>
                                <small class="text-primary">Lihat seluruh laporan</small>
                            </span>
                        </a>
                    </div>
                    @foreach (\App\Enums\ReportStatus::cases() as $status)
                        <div class="col-6 col-lg">
                            <a class="card navigation-card h-100 border-0 shadow-sm text-decoration-none" href="{{ route('kelurahan.reports.index', ['status' => $status->value]) }}#laporan">
                                <span class="card-body p-3 p-lg-4">
                                    <span class="text-secondary small d-block">{{ $status->label() }}</span>
                                    <strong class="fs-3 text-body d-block">{{ number_format($totalsByStatus[$status->value]) }}</strong>
                                    <small class="text-{{ $status->bootstrapColor() }}">Buka daftar terfilter</small>
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
                                            <td class="fw-semibold">{{ $rw->code }}</td><td>{{ $rw->name }}</td><td>{{ $rw->active_rts_count }}</td><td>{{ $rw->reports_count }}</td>
                                            <td><a href="{{ $rwUrl }}" class="small text-decoration-none">Lihat detail <span aria-hidden="true">→</span></a></td>
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
                            <div class="col-auto"><select name="rw_id" class="form-select" aria-label="Filter RW"><option value="">Semua RW</option>@foreach ($rws as $rw)<option value="{{ $rw->id }}" @selected((int) request('rw_id') === $rw->id)>{{ $rw->code }}</option>@endforeach</select></div>
                            <div class="col-auto"><select name="rt_id" class="form-select" aria-label="Filter RT"><option value="">Semua RT</option>@foreach ($rts as $rt)<option value="{{ $rt->id }}" @selected((int) request('rt_id') === $rt->id)>{{ $rt->code }}</option>@endforeach</select></div>
                            <div class="col-auto"><select name="status" class="form-select" aria-label="Filter status"><option value="">Semua Status</option>@foreach (\App\Enums\ReportStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
                            <div class="col-auto"><input name="search" value="{{ request('search') }}" class="form-control" aria-label="Cari laporan" placeholder="Tiket, warga, atau judul"></div>
                            <div class="col-auto"><button class="btn btn-primary">Cari</button></div>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Tiket</th><th>RW</th><th>RT</th><th>Warga</th><th>Judul</th><th>Status</th><th><span class="visually-hidden">Aksi</span></th></tr></thead>
                            <tbody>
                                @forelse ($reports as $report)
                                    <tr class="interactive-row" tabindex="0" data-row-url="{{ route($reportDetailRoute, $report) }}"><td>{{ $report->ticket_number }}</td><td>{{ $report->rt->rw->code }}</td><td>{{ $report->rt->code }}</td><td>{{ $report->citizen->name }}</td><td>{{ $report->title }}</td><td><span class="badge text-bg-{{ $report->status->bootstrapColor() }}">{{ $report->status->label() }}</span></td><td><a class="btn btn-outline-primary btn-sm" href="{{ route($reportDetailRoute, $report) }}" target="_blank" rel="noopener">Detail</a></td></tr>
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
