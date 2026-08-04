@extends('layouts.app')

@section('title', 'Dashboard RT - SIGAP WARGA')

@section('content')
    @php
        $user = auth()->user();
        $hasActiveFilters = request()->filled('status') || request()->filled('search');
        $recentReports = $reports->take(4);
    @endphp

    <div class="rt-dashboard min-vh-100">
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
                <div class="position-relative d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-4">
                    <div>
                        <span class="badge rounded-pill bg-white bg-opacity-10 border border-white border-opacity-25 px-3 py-2 mb-3">Dashboard RT</span>
                        <p class="text-white-50 mb-1">Selamat datang,</p>
                        <h1 class="display-6 fw-bold mb-3">{{ $user->name }}</h1>
                        <p class="mb-0 text-white-75">Pantau laporan warga dan koordinasikan tindak lanjut wilayah dalam satu halaman.</p>
                    </div>
                    <div class="hero-meta rounded-4 p-3 p-lg-4">
                        <div class="small text-white-50 text-uppercase fw-semibold mb-1">Wilayah tugas</div>
                        <div class="h5 fw-bold mb-2">{{ $user->rt?->code ?? 'RT belum tersedia' }} · {{ $user->rw?->code ?? 'RW belum tersedia' }}</div>
                        <div class="small text-white-75">{{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}</div>
                    </div>
                </div>
            </header>

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
                                <a class="quick-action d-flex align-items-center gap-3 rounded-3 border p-3 text-decoration-none" href="{{ route('reports.create') }}">
                                    <span class="quick-action-icon bg-success-subtle text-success" aria-hidden="true">02</span>
                                    <span><strong class="d-block text-body">Buat laporan</strong><small class="text-secondary">Buka formulir laporan warga</small></span>
                                </a>
                                <a class="quick-action d-flex align-items-center gap-3 rounded-3 border p-3 text-decoration-none" href="{{ route('tracking.index') }}">
                                    <span class="quick-action-icon bg-warning-subtle text-warning-emphasis" aria-hidden="true">03</span>
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
                                                <p class="text-secondary mb-3">{{ $hasActiveFilters ? 'Tidak ada data yang cocok. Coba gunakan kata kunci lain atau pilih status berbeda.' : 'Saat laporan pertama dikirim, detail dan status tindak lanjutnya akan tersedia di sini.' }}</p>
                                                @if ($hasActiveFilters)
                                                    <a class="btn btn-outline-primary btn-sm" href="{{ route('rt.dashboard') }}">Hapus semua filter</a>
                                                @else
                                                    <a class="btn btn-primary btn-sm" href="{{ route('reports.create') }}">Buat laporan pertama</a>
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
        </main>
    </div>
@endsection
