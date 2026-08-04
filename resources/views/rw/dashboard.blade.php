@extends('layouts.app')

@section('title', 'Dashboard RW - SIGAP WARGA')

@section('content')
    <nav class="navbar bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="{{ route('rw.dashboard') }}">SIGAP WARGA — RW</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-outline-danger btn-sm" type="submit">Keluar</button>
            </form>
        </div>
    </nav>

    <main class="container py-4">
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg"><div class="card h-100"><div class="card-body"><div class="text-secondary">Total</div><div class="fs-3 fw-semibold">{{ $total }}</div></div></div></div>
            @foreach (\App\Enums\ReportStatus::cases() as $status)
                <div class="col-6 col-lg"><div class="card h-100"><div class="card-body"><div class="text-secondary">{{ $status->value }}</div><div class="fs-3 fw-semibold">{{ $totalsByStatus[$status->value] }}</div></div></div></div>
            @endforeach
            <div class="col-6 col-lg"><div class="card h-100"><div class="card-body"><div class="text-secondary">RT Aktif</div><div class="fs-3 fw-semibold">{{ $activeRtCount }}</div></div></div></div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h1 class="h4">Total Laporan per RT</h1>
                <div class="row g-3">
                    @foreach ($rts as $rt)
                        <div class="col-sm-6 col-lg-4">
                            <div class="border rounded p-3">
                                <div class="text-secondary">{{ $rt->code }} — {{ $rt->name }}</div>
                                <div class="fs-4 fw-semibold">{{ $totalsByRt[$rt->id] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
                    <h2 class="h3 mb-0">Laporan Terbaru</h2>
                    <form method="GET" action="{{ route('rw.dashboard') }}" class="row g-2">
                        <div class="col-auto">
                            <select name="rt_id" class="form-select" aria-label="Filter RT">
                                <option value="">Semua RT</option>
                                @foreach ($rts as $rt)
                                    <option value="{{ $rt->id }}" @selected((int) request('rt_id') === $rt->id)>{{ $rt->code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <select name="status" class="form-select" aria-label="Filter status">
                                <option value="">Semua status</option>
                                @foreach (\App\Enums\ReportStatus::cases() as $status)
                                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->value }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Tiket, warga, atau judul"></div>
                        <div class="col-auto"><button class="btn btn-primary" type="submit">Terapkan</button></div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Tiket</th><th>RT</th><th>Warga</th><th>Judul</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($reports as $report)
                                <tr>
                                    <td>{{ $report->ticket_number }}</td>
                                    <td>{{ $report->rt->code }}</td>
                                    <td>{{ $report->citizen->name }}</td>
                                    <td>{{ $report->title }}</td>
                                    <td><span class="badge text-bg-primary">{{ $report->status->value }}</span></td>
                                    <td><a class="btn btn-outline-primary btn-sm" href="{{ route('rw.reports.show', $report) }}">Detail</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-secondary py-4">Belum ada laporan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $reports->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </main>
@endsection
