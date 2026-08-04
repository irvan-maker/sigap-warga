@extends('layouts.app')

@section('title', 'Lacak Laporan - SIGAP WARGA')

@section('content')
    <main class="container py-5" style="max-width: 860px;">
        <div class="text-center mb-4">
            <h1 class="h2">Lacak Laporan</h1>
            <p class="text-secondary mb-0">Masukkan nomor tiket dan nomor telepon pelapor.</p>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('tracking.store') }}">
                    @csrf

                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label for="ticket_number" class="form-label">Nomor tiket</label>
                            <input
                                id="ticket_number"
                                name="ticket_number"
                                type="text"
                                value="{{ old('ticket_number') }}"
                                class="form-control @error('ticket_number') is-invalid @enderror"
                                placeholder="SGW-2026-00001"
                                required
                            >
                            @error('ticket_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-5">
                            <label for="phone" class="form-label">Nomor telepon</label>
                            <input
                                id="phone"
                                name="phone"
                                type="tel"
                                value="{{ old('phone') }}"
                                class="form-control @error('phone') is-invalid @enderror"
                                placeholder="0812 3456 7890"
                                required
                            >
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @error('phone_normalized')
                                <div class="text-danger small mt-1">Nomor telepon tidak valid.</div>
                            @enderror
                        </div>

                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-primary">Lacak</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if ($searched && ! $report)
            <div class="alert alert-warning" role="alert">Laporan tidak ditemukan.</div>
        @elseif ($report)
            <section class="card border-0 shadow-sm" aria-labelledby="report-heading">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-4">
                        <div>
                            <p class="text-secondary small mb-1">Nomor tiket</p>
                            <h2 id="report-heading" class="h4 mb-0">{{ $report->ticket_number }}</h2>
                        </div>
                        <span class="badge text-bg-primary align-self-start">{{ $report->status->value }}</span>
                    </div>

                    <dl class="row mb-4">
                        <dt class="col-sm-4">Nama warga</dt>
                        <dd class="col-sm-8">{{ $report->citizen->name }}</dd>

                        <dt class="col-sm-4">RT</dt>
                        <dd class="col-sm-8">{{ $report->rt->code }} — {{ $report->rt->name }}</dd>

                        <dt class="col-sm-4">Judul</dt>
                        <dd class="col-sm-8">{{ $report->title }}</dd>

                        <dt class="col-sm-4">Deskripsi</dt>
                        <dd class="col-sm-8">{{ $report->description }}</dd>

                        <dt class="col-sm-4">Tanggal laporan</dt>
                        <dd class="col-sm-8">{{ $report->reported_at->format('d-m-Y H:i') }}</dd>
                    </dl>

                    <h3 class="h5">Riwayat status</h3>
                    <ol class="list-group list-group-numbered">
                        @foreach ($report->histories as $history)
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between gap-3">
                                    <strong>{{ $history->new_status->value }}</strong>
                                    <time class="text-secondary small">{{ $history->created_at->format('d-m-Y H:i') }}</time>
                                </div>
                                @if ($history->note)
                                    <p class="mb-0 mt-2">{{ $history->note }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>
        @endif
    </main>
@endsection
