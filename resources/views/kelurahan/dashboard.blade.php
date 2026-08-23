@extends('layouts.app')

@section('title', 'Dashboard Desa - SIGAP WARGA')

@section('content')
    @php
        $officer = auth()->user();
        $reportDetailRoute = $officer->isSystemAdmin() ? 'reports.show' : 'kelurahan.reports.show';
        $canManageRws = $officer->isSystemAdmin() || $officer->isVillageSecretary();
        $navigation = [
            ['label' => 'Dashboard', 'url' => route('kelurahan.dashboard'), 'icon' => 'bi-grid-1x2', 'active' => true],
            ['label' => 'Laporan', 'url' => route('kelurahan.reports.index').'#laporan', 'icon' => 'bi-inbox'],
            ['label' => 'Wilayah', 'url' => route('kelurahan.rws.index'), 'icon' => 'bi-diagram-3'],
            ['label' => 'Warga', 'url' => route('kelurahan.citizens.index'), 'icon' => 'bi-people'],
            ['label' => 'Kartu Keluarga', 'url' => route('kelurahan.family-cards.index'), 'icon' => 'bi-card-heading'],
            ['label' => 'Surat', 'url' => route('kelurahan.letters.index'), 'icon' => 'bi-envelope-check'],
            ['label' => 'Master Surat', 'url' => route('kelurahan.letter-types.index'), 'icon' => 'bi-sliders'],
        ];
    @endphp

    <div class="dashboard-workspace kelurahan-dashboard">
        <x-dashboard.topbar :home-url="route('kelurahan.dashboard')" role-label="Dashboard Desa" :context="$officer->position?->label() ?? 'Petugas Desa'" :links="$navigation" />

        <main id="main-content" class="container dashboard-main">
            <x-dashboard.hero badge="Layanan aktif" title="Selamat datang, {{ $officer->name }}" description="Pusat pemantauan laporan warga, koordinasi wilayah, dan operasional {{ config('village.name') }}." icon="bi-buildings">
                <x-slot:meta><small class="d-block mb-1">{{ $officer->position?->label() }}</small><strong class="d-block">{{ config('village.name') }}</strong><span class="small text-white-50">{{ now()->timezone('Asia/Jakarta')->locale('id')->isoFormat('dddd, D MMMM Y') }}</span></x-slot:meta>
            </x-dashboard.hero>

            <section class="module-strip" aria-label="Status modul sistem">
                <div class="module-strip-copy"><span class="module-strip-icon"><i class="bi bi-boxes" aria-hidden="true"></i></span><div><strong class="d-block">Tahap Uji Lokal</strong><small class="text-secondary">Desa memantau agregat dan eskalasi dari wilayah.</small></div></div>
                <div class="module-pills"><span class="module-pill module-pill-active">Laporan Cepat · PILOT</span><span class="module-pill module-pill-prototype">Sensus · PROTOTYPE</span><span class="module-pill module-pill-prototype">Posyandu · AGREGAT {{ number_format($posyanduMonthlyVisitCount) }}</span><span class="module-pill module-pill-prototype">Persuratan · PROTOTYPE</span></div>
            </section>

            <section class="dashboard-section" aria-labelledby="kpi-heading">
                <x-dashboard.section-heading eyebrow="Layanan warga" title="Ringkasan Laporan" description="Cakupan data dan pekerjaan pada tingkat desa." heading-id="kpi-heading" />
                <div class="row g-3 mb-3">
                    <div class="col-sm-6 col-xl-3"><x-dashboard.metric label="Total Laporan" :value="number_format($total)" helper="Seluruh wilayah" icon="bi-inbox" tone="primary" :href="route('kelurahan.reports.index').'#laporan'" /></div>
                    <div class="col-sm-6 col-xl-3"><x-dashboard.metric label="Administrasi Surat" :value="number_format($letterCount)" helper="Pengajuan tercatat" icon="bi-envelope-check" tone="warning" :href="route('kelurahan.letters.index')" /></div>
                    <div class="col-sm-6 col-xl-3"><x-dashboard.metric label="Warga Aktif" :value="number_format($activeCitizenCount)" helper="Data warga" icon="bi-people" tone="success" :href="route('kelurahan.citizens.index')" /></div>
                    <div class="col-sm-6 col-xl-3"><x-dashboard.metric label="KK Aktif" :value="number_format($activeFamilyCardCount)" helper="Data keluarga" icon="bi-card-heading" tone="info" :href="route('kelurahan.family-cards.index')" /></div>
                </div>
                <div class="row g-3">
                    @foreach (\App\Enums\ReportStatus::cases() as $status)
                        <div class="col-6 col-md-3">
                            <a class="card dashboard-panel-modern h-100 text-decoration-none" href="{{ route('kelurahan.reports.index', ['status' => $status->value]) }}#laporan">
                                <span class="card-body p-3 d-flex flex-column">
                                    <span class="text-secondary small mb-1">{{ $status->label() }}</span>
                                    <strong class="fs-4 text-body">{{ number_format($totalsByStatus[$status->value]) }}</strong>
                                    <small class="text-{{ $status->bootstrapColor() }} mt-auto pt-2">Lihat daftar</small>
                                </span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="dashboard-section" aria-labelledby="actions-heading">
                <x-dashboard.section-heading eyebrow="Operasional" title="Aksi Cepat" description="Menu disesuaikan dengan kewenangan petugas yang sedang masuk." heading-id="actions-heading" />
                <div class="row g-3">
                    <div class="col-sm-6 col-xl-4">
                        <x-dashboard.action-card :href="route('admin.reports.index')" title="Kelola Laporan" :description="$officer->isVillageHead() ? 'Pantau seluruh laporan warga' : 'Cari dan tindak lanjuti laporan warga'" icon="bi-inbox" />
                    </div>
                    <div class="col-sm-6 col-xl-4">
                        <x-dashboard.action-card :href="route('kelurahan.rws.index')" :title="$officer->isVillageHead() ? 'Lihat RW' : 'Kelola RW'" :description="$officer->isVillageHead() ? 'Lihat struktur wilayah RW' : 'Atur data dan status wilayah RW'" icon="bi-diagram-3" tone="success" />
                    </div>
                    @if ($officer->isSystemAdmin() || $officer->isVillageSecretary())
                        <div class="col-sm-6 col-xl-4">
                            <x-dashboard.action-card :href="route('admin.users.index')" title="Kelola Akun Petugas" description="Atur akun dan penempatan petugas" icon="bi-people" tone="warning" />
                        </div>
                        <div class="col-sm-6 col-xl-4">
                            <x-dashboard.action-card :href="route('kelurahan.letter-types.index')" title="Master Jenis Surat" description="Atur versioned configuration Persuratan" icon="bi-sliders" tone="info" />
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
                                <a class="attention-link rounded-3 p-3 text-decoration-none" href="{{ route('kelurahan.reports.index') }}#laporan"><span class="text-body">{{ number_format($attentionSummary['overdue_response']) }} laporan melewati batas respons</span><span aria-hidden="true">→</span></a>
                                <a class="attention-link rounded-3 p-3 text-decoration-none" href="{{ route('kelurahan.reports.index') }}#laporan"><span class="text-body">{{ number_format($attentionSummary['overdue_resolution']) }} laporan melewati target penyelesaian</span><span aria-hidden="true">→</span></a>
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
                                    <tr class="interactive-row" tabindex="0" data-row-url="{{ route($reportDetailRoute, $report) }}"><td>{{ $report->ticket_number }}</td><td>{{ $report->rt->rw->code }}</td><td>{{ $report->rt->code }}</td><td>{{ $report->citizen?->name ?? 'Pelapor umum' }}</td><td>{{ $report->title }}</td><td><span class="badge text-bg-{{ $report->status->bootstrapColor() }}">{{ $report->status->label() }}</span></td><td><a class="btn btn-outline-primary btn-sm" href="{{ route($reportDetailRoute, $report) }}" target="_blank" rel="noopener">Detail</a></td></tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-secondary py-5">Belum ada laporan yang sesuai dengan filter.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $reports->links('pagination::bootstrap-5') }}
                </div>
            </section>

            @include('analytics.village')
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
