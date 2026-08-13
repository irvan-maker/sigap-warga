@extends('layouts.app')
@section('title', 'Lacak Laporan - '.config('village.name'))
@section('content')
<main id="main-content" class="container py-5" style="max-width: 860px">
    <a href="{{ route('public.home') }}" class="d-inline-block mb-4"><i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke beranda</a>
    <div class="text-center mb-4"><h1 class="h2">Lacak Laporan</h1><p class="text-secondary">Masukkan nomor tiket dan nomor HP yang digunakan saat laporan dibuat. <a href="{{ route('public.privacy') }}">Informasi privasi</a>.</p></div>
    <div class="card mb-4"><div class="card-body p-4"><form method="POST" action="{{ route('tracking.store') }}">@csrf<div class="row g-3 align-items-end">
        <div class="col-md-5"><label for="ticket_number" class="form-label">Nomor tiket</label><input id="ticket_number" name="ticket_number" value="{{ old('ticket_number') }}" class="form-control @error('ticket_number') is-invalid @enderror" placeholder="SGW-2026-00001" required aria-describedby="ticket-help"><div id="ticket-help" class="form-text">Nomor tiket diterima setelah laporan dicatat.</div>@error('ticket_number')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-5"><label for="phone" class="form-label">Nomor HP</label><input id="phone" name="phone" type="tel" autocomplete="tel" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" placeholder="0812 3456 7890" required aria-describedby="report-phone-help"><div id="report-phone-help" class="form-text">Format 08xx atau +62. Hanya digunakan untuk verifikasi.</div>@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror @error('phone_normalized')<div class="text-danger small mt-1">Nomor HP tidak valid.</div>@enderror</div>
        <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit"><i class="bi bi-search me-1" aria-hidden="true"></i>Lacak</button></div>
    </div></form></div></div>
    @if($searched && !$report)<div class="alert alert-warning" role="alert"><strong>Data belum dapat ditemukan.</strong> Periksa kembali nomor tiket dan nomor HP. Demi keamanan, kami tidak dapat memberi tahu bagian mana yang tidak cocok.</div>@endif
    @if($report)<section class="card" aria-labelledby="report-heading"><div class="card-body p-4"><div class="d-flex flex-wrap justify-content-between gap-2 mb-4"><div><p class="text-secondary small mb-1">Nomor tiket</p><h2 id="report-heading" class="h4">{{ $report->ticket_number }}</h2></div><span class="badge text-bg-{{ $report->status->bootstrapColor() }} align-self-start">{{ $report->status->label() }}</span></div>
        <dl class="row mb-4"><dt class="col-sm-4">Judul</dt><dd class="col-sm-8">{{ $report->title }}</dd><dt class="col-sm-4">Tanggal laporan</dt><dd class="col-sm-8">{{ $report->reported_at->format('d-m-Y H:i') }}</dd></dl>
        @include('reports.partials.attachments', ['attachments' => $report->attachments])
        <h3 class="h5">Riwayat status</h3><ol class="list-group list-group-numbered">@foreach($report->histories as $history)<li class="list-group-item"><div class="d-flex justify-content-between gap-3"><strong>{{ $history->new_status->label() }}</strong><time class="text-secondary small">{{ $history->created_at->format('d-m-Y H:i') }}</time></div>@if($history->public_note)<p class="mb-0 mt-2">{{ $history->public_note }}</p>@endif</li>@endforeach</ol>
    </div></section>@endif
</main>
@endsection
