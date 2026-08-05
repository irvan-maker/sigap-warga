@extends('layouts.app')

@section('title', 'Dashboard Kelurahan - SIGAP WARGA')

@section('content')
    <nav class="navbar bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="{{ route('kelurahan.dashboard') }}">SIGAP WARGA — Kelurahan</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline-danger btn-sm" type="submit">Keluar</button></form>
        </div>
    </nav>

    <main class="container py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><h1 class="h3 mb-1">{{ auth()->user()->name }}</h1><div class="text-secondary">{{ auth()->user()->position?->label() }}</div></div><div class="d-flex gap-2"><a class="btn btn-outline-primary" href="{{ route('admin.reports.index') }}">Laporan</a><a class="btn btn-primary" href="{{ route('kelurahan.rws.index') }}">{{ auth()->user()->isVillageHead() ? 'Lihat RW' : 'Kelola RW' }}</a>@if(auth()->user()->isSystemAdmin() || auth()->user()->isVillageSecretary())<a class="btn btn-success" href="{{ route('admin.users.index') }}">Kelola Akun Petugas</a>@endif</div></div>
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg"><div class="card h-100"><div class="card-body"><div class="text-secondary">Total Laporan</div><div class="fs-3 fw-semibold">{{ $total }}</div></div></div></div>
            @foreach (\App\Enums\ReportStatus::cases() as $status)
                <div class="col-6 col-lg"><div class="card h-100"><div class="card-body"><div class="text-secondary">{{ $status->value }}</div><div class="fs-3 fw-semibold">{{ $totalsByStatus[$status->value] }}</div></div></div></div>
            @endforeach
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-secondary">Total RW</div><div class="fs-3">{{ $totalRw }}</div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-secondary">RW Aktif</div><div class="fs-3">{{ $activeRw }}</div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-secondary">Total RT</div><div class="fs-3">{{ $totalRt }}</div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-secondary">RT Aktif</div><div class="fs-3">{{ $activeRt }}</div></div></div></div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-6"><div class="card h-100 border-0 shadow-sm"><div class="card-body p-4">
                <h1 class="h4">Laporan per RW</h1>
                @foreach ($rws as $rw)<div class="d-flex justify-content-between border-bottom py-2"><span>{{ $rw->code }} — {{ $rw->name }}</span><strong>{{ $totalsByRw[$rw->id] }}</strong></div>@endforeach
            </div></div></div>
            <div class="col-lg-6"><div class="card h-100 border-0 shadow-sm"><div class="card-body p-4">
                <h2 class="h4">Laporan per RT</h2>
                @foreach ($rts as $rt)<div class="d-flex justify-content-between border-bottom py-2"><span>{{ $rt->code }} — {{ $rt->name }}</span><strong>{{ $totalsByRt[$rt->id] }}</strong></div>@endforeach
            </div></div></div>
        </div>

        <div class="card border-0 shadow-sm"><div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
                <h2 class="h3 mb-0">Laporan Terbaru</h2>
                <form method="GET" action="{{ route('kelurahan.dashboard') }}" class="row g-2">
                    <div class="col-auto"><select name="rw_id" class="form-select"><option value="">Semua RW</option>@foreach ($rws as $rw)<option value="{{ $rw->id }}" @selected((int) request('rw_id') === $rw->id)>{{ $rw->code }}</option>@endforeach</select></div>
                    <div class="col-auto"><select name="rt_id" class="form-select"><option value="">Semua RT</option>@foreach ($rts as $rt)<option value="{{ $rt->id }}" @selected((int) request('rt_id') === $rt->id)>{{ $rt->code }}</option>@endforeach</select></div>
                    <div class="col-auto"><select name="status" class="form-select"><option value="">Semua Status</option>@foreach (\App\Enums\ReportStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->value }}</option>@endforeach</select></div>
                    <div class="col-auto"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Tiket, warga, atau judul"></div>
                    <div class="col-auto"><button class="btn btn-primary">Terapkan</button></div>
                </form>
            </div>

            <div class="table-responsive"><table class="table table-hover align-middle">
                <thead><tr><th>Tiket</th><th>RW</th><th>RT</th><th>Warga</th><th>Judul</th><th>Status</th><th></th></tr></thead>
                <tbody>@forelse ($reports as $report)<tr>
                    <td>{{ $report->ticket_number }}</td><td>{{ $report->rt->rw->code }}</td><td>{{ $report->rt->code }}</td><td>{{ $report->citizen->name }}</td><td>{{ $report->title }}</td><td><span class="badge text-bg-primary">{{ $report->status->value }}</span></td><td><a class="btn btn-outline-primary btn-sm" href="{{ route('kelurahan.reports.show', $report) }}">Detail</a></td>
                </tr>@empty<tr><td colspan="7" class="text-center text-secondary py-4">Belum ada laporan.</td></tr>@endforelse</tbody>
            </table></div>
            {{ $reports->links('pagination::bootstrap-5') }}
        </div></div>
    </main>
@endsection
