@extends('layouts.app')

@section('title', 'Detail Laporan RW - SIGAP WARGA')

@section('content')
    <main class="container py-5" style="max-width: 900px;">
        <a href="{{ route('rw.dashboard') }}" class="btn btn-outline-secondary mb-4">Kembali</a>

        <div class="d-flex justify-content-between gap-3 mb-4">
            <div><p class="text-secondary mb-1">Nomor tiket</p><h1 class="h2">{{ $report->ticket_number }}</h1></div>
            <span class="badge text-bg-primary align-self-start fs-6">{{ $report->status->value }}</span>
        </div>

        <div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
            <dl class="row mb-0">
                <dt class="col-sm-4">Warga</dt><dd class="col-sm-8">{{ $report->citizen->name }}</dd>
                <dt class="col-sm-4">RT</dt><dd class="col-sm-8">{{ $report->rt->code }} — {{ $report->rt->name }}</dd>
                <dt class="col-sm-4">Judul</dt><dd class="col-sm-8">{{ $report->title }}</dd>
                <dt class="col-sm-4">Deskripsi</dt><dd class="col-sm-8">{{ $report->description }}</dd>
                <dt class="col-sm-4">Tanggal laporan</dt><dd class="col-sm-8 mb-0">{{ $report->reported_at->format('d-m-Y H:i') }}</dd>
            </dl>
        </div></div>

        <div class="card border-0 shadow-sm"><div class="card-body p-4">
            <h2 class="h4">Riwayat Status</h2>
            <ol class="list-group list-group-numbered">
                @foreach ($histories as $history)
                    <li class="list-group-item">
                        <strong>{{ $history->new_status->value }}</strong>
                        <span class="text-secondary small ms-2">{{ $history->created_at->format('d-m-Y H:i') }}</span>
                        @if ($history->note)<p class="mb-0 mt-2">{{ $history->note }}</p>@endif
                    </li>
                @endforeach
            </ol>
        </div></div>
    </main>
@endsection
