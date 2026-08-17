@extends('layouts.app')
@section('title', 'Lacak Laporan - '.config('village.name'))
@section('content')
<main id="main-content" class="container py-4 py-md-5" style="max-width: 920px">
    <a href="{{ route('public.home') }}" class="btn btn-sm btn-outline-secondary mb-4">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Kembali
    </a>

    <header class="text-center mb-4 mb-md-5">
        <span class="badge rounded-pill text-bg-primary-subtle text-primary-emphasis mb-2">SIGAP WARGA</span>
        <h1 class="h2 fw-bold mb-2">Lacak Laporan</h1>
        <p class="text-secondary mb-0">
            Masukkan nomor tiket dan nomor WhatsApp/HP yang digunakan saat laporan dibuat.
            <a href="{{ route('public.privacy') }}">Informasi privasi</a>.
        </p>
    </header>

    <section class="card border-0 shadow-sm mb-4" aria-label="Form pelacakan laporan">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('tracking.store') }}">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="ticket_number" class="form-label fw-semibold">Nomor tiket</label>
                        <input id="ticket_number" name="ticket_number" value="{{ old('ticket_number') }}" class="form-control @error('ticket_number') is-invalid @enderror" placeholder="SGW-2026-00001" required aria-describedby="ticket-help">
                        <div id="ticket-help" class="form-text">Nomor tiket diterima setelah laporan dicatat.</div>
                        @error('ticket_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <label for="phone" class="form-label fw-semibold">Nomor WhatsApp / HP</label>
                        <input id="phone" name="phone" type="tel" autocomplete="tel" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" placeholder="0812 3456 7890" required aria-describedby="report-phone-help">
                        <div id="report-phone-help" class="form-text">Format 08xx atau +62. Hanya digunakan untuk verifikasi.</div>
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @error('phone_normalized')<div class="text-danger small mt-1">Nomor HP tidak valid.</div>@enderror
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1" aria-hidden="true"></i>Lacak</button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    @if($searched && !$report)
        <div class="alert alert-warning border-0 shadow-sm" role="alert">
            <strong>Data belum dapat ditemukan.</strong> Periksa kembali nomor tiket dan nomor WhatsApp/HP. Demi keamanan, kami tidak dapat memberi tahu bagian mana yang tidak cocok.
        </div>
    @endif

    @if($report)
        @php
            $publicLabel = fn ($status) => match ($status) {
                \App\Enums\ReportStatus::NEW => 'Laporan diterima',
                \App\Enums\ReportStatus::PROCESSING => 'Sedang ditangani',
                \App\Enums\ReportStatus::FORWARDED => 'Diteruskan',
                \App\Enums\ReportStatus::COMPLETED => 'Selesai',
                \App\Enums\ReportStatus::REJECTED => 'Tidak dapat ditindaklanjuti',
            };
            $publicIcon = fn ($status) => match ($status) {
                \App\Enums\ReportStatus::NEW => 'bi-inbox',
                \App\Enums\ReportStatus::PROCESSING => 'bi-tools',
                \App\Enums\ReportStatus::FORWARDED => 'bi-arrow-up-right-circle',
                \App\Enums\ReportStatus::COMPLETED => 'bi-check-circle',
                \App\Enums\ReportStatus::REJECTED => 'bi-x-circle',
            };
            $publicColor = fn ($status) => match ($status) {
                \App\Enums\ReportStatus::NEW => 'primary',
                \App\Enums\ReportStatus::PROCESSING => 'warning',
                \App\Enums\ReportStatus::FORWARDED => 'info',
                \App\Enums\ReportStatus::COMPLETED => 'success',
                \App\Enums\ReportStatus::REJECTED => 'danger',
            };
            $fallbackNote = fn ($status) => match ($status) {
                \App\Enums\ReportStatus::NEW => 'Laporan telah diterima dan menunggu verifikasi petugas wilayah.',
                \App\Enums\ReportStatus::PROCESSING => 'Laporan telah diverifikasi petugas dan sedang ditindaklanjuti.',
                \App\Enums\ReportStatus::FORWARDED => 'Laporan membutuhkan koordinasi pada tingkat penanganan berikutnya.',
                \App\Enums\ReportStatus::COMPLETED => 'Penanganan laporan telah dinyatakan selesai.',
                \App\Enums\ReportStatus::REJECTED => 'Laporan tidak dapat ditindaklanjuti. Silakan lihat pembaruan petugas untuk penjelasan lebih lanjut.',
            };
        @endphp

        <section class="card border-0 shadow-sm mb-4" aria-labelledby="report-heading">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                    <div>
                        <p class="text-secondary small mb-1">Nomor tiket</p>
                        <h2 id="report-heading" class="h3 fw-bold mb-1">{{ $report->ticket_number }}</h2>
                        <div class="text-secondary">{{ $report->rt?->rw?->code }} / {{ $report->rt?->code }}</div>
                    </div>
                    <span class="badge rounded-pill text-bg-{{ $publicColor($report->status) }} fs-6 px-3 py-2">
                        <i class="bi {{ $publicIcon($report->status) }} me-1" aria-hidden="true"></i>{{ $publicLabel($report->status) }}
                    </span>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <div class="rounded-4 bg-light p-3 h-100">
                            <div class="text-secondary small mb-1">Laporan</div>
                            <div class="fw-semibold mb-1">{{ $report->title }}</div>
                            <p class="mb-0">{{ $report->description }}</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="rounded-4 bg-light p-3 h-100">
                            <div class="text-secondary small mb-1">Tanggal laporan</div>
                            <div class="fw-semibold">{{ $report->reported_at->format('d-m-Y H:i') }}</div>
                            <div class="text-secondary small mt-3">Wilayah penanganan awal</div>
                            <div class="fw-semibold">{{ $report->rt?->code ?? '—' }}{{ $report->rt?->name ? ' — '.$report->rt->name : '' }}</div>
                        </div>
                    </div>
                </div>

                @include('reports.partials.attachments', ['attachments' => $report->attachments])
            </div>
        </section>

        <section class="card border-0 shadow-sm" aria-labelledby="timeline-heading">
            <div class="card-body p-4 p-md-5">
                <div class="mb-4">
                    <p class="text-primary fw-semibold small text-uppercase mb-1">Perkembangan laporan</p>
                    <h3 id="timeline-heading" class="h4 fw-bold mb-1">Riwayat penanganan</h3>
                    <p class="text-secondary mb-0">Pembaruan berikut merupakan informasi yang aman untuk ditampilkan kepada warga.</p>
                </div>

                <div class="position-relative">
                    @foreach($report->histories as $history)
                        <article class="d-flex gap-3 {{ $loop->last ? '' : 'pb-4' }}">
                            <div class="d-flex flex-column align-items-center" style="width: 38px; flex: 0 0 38px;">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle text-bg-{{ $publicColor($history->new_status) }}" style="width: 36px; height: 36px; z-index: 1;">
                                    <i class="bi {{ $publicIcon($history->new_status) }}" aria-hidden="true"></i>
                                </span>
                                @unless($loop->last)
                                    <span class="bg-secondary-subtle flex-grow-1" style="width: 2px; min-height: 46px;"></span>
                                @endunless
                            </div>
                            <div class="flex-grow-1 rounded-4 border p-3 p-md-4">
                                <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                                    <strong>{{ $publicLabel($history->new_status) }}</strong>
                                    <time class="text-secondary small">{{ $history->created_at->format('d-m-Y H:i') }}</time>
                                </div>
                                <p class="mb-0">{{ filled($history->public_note) ? $history->public_note : $fallbackNote($history->new_status) }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</main>
@endsection
