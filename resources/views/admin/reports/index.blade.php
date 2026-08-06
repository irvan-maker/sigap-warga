@extends('layouts.app')

@section('title', 'Kelola Laporan - SIGAP WARGA')

@section('content')
    <div class="admin-dashboard min-vh-100">
        <nav class="navbar bg-white border-bottom sticky-top" aria-label="Navigasi admin">
            <div class="container py-1">
                <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-primary" href="{{ route('dashboard') }}">
                    <span class="admin-brand-mark text-white" aria-hidden="true">SW</span>
                    <span>SIGAP WARGA</span>
                </a>
                <div class="d-flex align-items-center gap-2 gap-sm-3">
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('dashboard') }}">Dashboard</a>
                    <div class="d-none d-md-block text-end lh-sm">
                        <span class="small fw-semibold d-block">{{ auth()->user()->name }}</span>
                        <span class="text-secondary small">Administrator</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm">Keluar</button>
                    </form>
                </div>
            </div>
        </nav>

        <main class="container py-4 py-lg-5">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
                <div>
                    <p class="admin-eyebrow mb-1">Operasional Administrator</p>
                    <h1 class="h2 fw-bold mb-1">Kelola Laporan</h1>
                    <p class="text-secondary mb-0">Cari dan tinjau seluruh laporan warga dari semua wilayah.</p>
                </div>
                <span class="badge rounded-pill text-bg-primary px-3 py-2">{{ number_format($reports->total()) }} laporan ditemukan</span>
            </div>

            <section class="card admin-panel border-0 shadow-sm mb-4" aria-labelledby="filter-heading">
                <div class="card-body p-4">
                    <h2 id="filter-heading" class="h5 fw-bold mb-3">Filter Laporan</h2>

                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            Filter tidak valid. Periksa kembali rentang tanggal dan pilihan filter.
                        </div>
                    @endif

                    <form method="GET" action="{{ route('admin.reports.index') }}" class="row g-3 align-items-end">
                        <div class="col-lg-4">
                            <label for="search" class="form-label">Pencarian</label>
                            <input id="search" name="search" type="search" value="{{ request('search') }}" class="form-control" placeholder="Tiket, warga, atau judul">
                        </div>
                        <div class="col-sm-6 col-lg-2">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-select">
                                <option value="">Semua status</option>
                                @foreach (\App\Enums\ReportStatus::cases() as $status)
                                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label for="rw_id" class="form-label">RW</label>
                            <select id="rw_id" name="rw_id" class="form-select">
                                <option value="">Semua RW</option>
                                @foreach ($rws as $rw)
                                    <option value="{{ $rw->id }}" @selected((int) request('rw_id') === $rw->id)>{{ $rw->code }} — {{ $rw->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label for="rt_id" class="form-label">RT</label>
                            <select id="rt_id" name="rt_id" class="form-select">
                                <option value="">Semua RT</option>
                                @foreach ($rts as $rt)
                                    <option value="{{ $rt->id }}" @selected((int) request('rt_id') === $rt->id)>{{ $rt->rw?->code }} · {{ $rt->code }} — {{ $rt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label for="date_from" class="form-label">Tanggal mulai</label>
                            <input id="date_from" name="date_from" type="date" value="{{ request('date_from') }}" class="form-control">
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label for="date_to" class="form-label">Tanggal akhir</label>
                            <input id="date_to" name="date_to" type="date" value="{{ request('date_to') }}" class="form-control">
                        </div>
                        <div class="col-lg-6 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary px-4">Cari</button>
                            <a class="btn btn-outline-secondary" href="{{ route('admin.reports.index') }}">Reset</a>
                        </div>
                    </form>
                </div>
            </section>

            <section class="card admin-panel border-0 shadow-sm" aria-labelledby="reports-heading">
                <div class="card-header bg-white border-0 p-4 pb-2">
                    <h2 id="reports-heading" class="h5 fw-bold mb-0">Daftar Laporan</h2>
                </div>
                <div class="card-body p-0 pt-2">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Tiket</th>
                                    <th>Laporan</th>
                                    <th>Warga</th>
                                    <th>Wilayah</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th class="pe-4 text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($reports as $report)
                                    <tr
                                        class="admin-report-row"
                                        data-report-url="{{ route('reports.show', $report) }}"
                                        tabindex="0"
                                        aria-label="Buka detail laporan {{ $report->ticket_number }}"
                                    >
                                        <td class="ps-4 fw-semibold text-nowrap">{{ $report->ticket_number }}</td>
                                        <td style="min-width: 14rem">{{ $report->title }}</td>
                                        <td class="text-nowrap">{{ $report->citizen?->name ?? 'Warga tidak tersedia' }}</td>
                                        <td class="text-nowrap">{{ $report->rt?->rw?->code ?? 'RW —' }} · {{ $report->rt?->code ?? 'RT —' }}</td>
                                        <td><span class="badge rounded-pill text-bg-{{ $report->status->bootstrapColor() }} px-3 py-2">{{ $report->status->label() }}</span></td>
                                        <td class="text-nowrap">{{ $report->reported_at?->format('d M Y, H:i') ?? '—' }}</td>
                                        <td class="pe-4 text-end"><a class="btn btn-outline-primary btn-sm" href="{{ route('reports.show', $report) }}">Detail</a></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center px-4 py-5">
                                            <div class="admin-empty-state mx-auto">
                                                <div class="admin-empty-icon mx-auto mb-3" aria-hidden="true">L</div>
                                                <h3 class="h6 fw-bold">Laporan tidak ditemukan</h3>
                                                <p class="text-secondary small mb-0">Belum ada laporan atau tidak ada data yang cocok dengan filter.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($reports->hasPages())
                        <div class="border-top px-4 py-3">
                            {{ $reports->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>
            </section>
        </main>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-report-url]').forEach((row) => {
            const navigateToReport = () => window.location.assign(row.dataset.reportUrl);

            row.addEventListener('click', (event) => {
                const target = event.target;
                const isInteractive = target instanceof Element
                    && target.closest('a, button, input, select, textarea, label');

                if (event.defaultPrevented || isInteractive || event.button !== 0
                    || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) {
                    return;
                }

                navigateToReport();
            });

            row.addEventListener('keydown', (event) => {
                if (event.target === row && event.key === 'Enter') {
                    event.preventDefault();
                    navigateToReport();
                }
            });
        });
    </script>
@endpush
