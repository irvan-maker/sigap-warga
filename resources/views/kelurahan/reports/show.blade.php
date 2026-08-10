@extends('layouts.app')

@section('title', 'Detail Laporan Kelurahan - SIGAP WARGA')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ==========================================================
           SIGAP WARGA — Detail Laporan: Glassmorphism (blue accent)
           Sama persis dengan sistem desain Dashboard Kelurahan
           ========================================================== */
        .report-detail-page {
            --gl-blue: #2b5cff;
            --gl-blue-dark: #1a3fd6;
            --gl-blue-light: #6d90ff;
            --gl-ink: #1c2340;
            --gl-muted: #5a6485;
            --gl-glass-bg: rgba(255, 255, 255, 0.55);
            --gl-glass-border: rgba(255, 255, 255, 0.65);
            --gl-glass-shadow: 0 8px 32px rgba(31, 60, 136, 0.14);

            position: relative;
            min-height: 100vh;
            overflow-x: hidden;
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            color: var(--gl-ink);
            background: linear-gradient(165deg, #eef2fc 0%, #e6ecfb 45%, #eef4fb 100%);
        }

        .report-detail-page h1,
        .report-detail-page h2 {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            font-weight: 800;
        }

        /* floating blurred color blobs = the "glass" needs something to refract */
        .report-detail-page .glass-blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            z-index: 0;
            pointer-events: none;
        }
        .report-detail-page .glass-blob--1 {
            width: 420px; height: 420px;
            top: -12%; left: -10%;
            background: radial-gradient(circle, rgba(43,92,255,0.5), transparent 70%);
        }
        .report-detail-page .glass-blob--2 {
            width: 380px; height: 380px;
            top: 25%; right: -14%;
            background: radial-gradient(circle, rgba(124,92,255,0.4), transparent 70%);
        }
        .report-detail-page .glass-blob--3 {
            width: 420px; height: 420px;
            bottom: -16%; left: 20%;
            background: radial-gradient(circle, rgba(52,209,200,0.35), transparent 70%);
        }

        .report-detail-page main.container {
            position: relative;
            z-index: 1;
        }

        /* ---------- Glass cards ---------- */
        .report-detail-page .card {
            background: var(--gl-glass-bg) !important;
            backdrop-filter: blur(18px) saturate(180%);
            -webkit-backdrop-filter: blur(18px) saturate(180%);
            border: 1px solid var(--gl-glass-border) !important;
            border-radius: 20px !important;
            box-shadow: var(--gl-glass-shadow) !important;
        }

        /* ---------- Definition list ---------- */
        .report-detail-page dt {
            color: var(--gl-muted);
            font-weight: 600;
        }
        .report-detail-page dd {
            font-weight: 500;
        }
        .report-detail-page .row > * {
            padding-top: .5rem;
            padding-bottom: .5rem;
        }

        /* ---------- Badge status ---------- */
        .report-detail-page .badge {
            font-weight: 700;
            backdrop-filter: blur(6px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            background: linear-gradient(135deg, var(--gl-blue), var(--gl-blue-light)) !important;
            border-radius: 10px;
            padding: .5em .9em;
        }

        /* ---------- Buttons ---------- */
        .report-detail-page .btn {
            border-radius: 12px;
            font-weight: 700;
        }
        .report-detail-page .btn-outline-secondary {
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(28, 35, 64, 0.2);
            color: var(--gl-ink);
            backdrop-filter: blur(6px);
        }
        .report-detail-page .btn-outline-secondary:hover {
            background: rgba(255, 255, 255, 0.8);
        }

        /* ---------- Riwayat status (list-group) ---------- */
        .report-detail-page .list-group-item {
            background: rgba(255, 255, 255, 0.45) !important;
            border: 1px solid rgba(43, 92, 255, 0.12) !important;
            border-radius: 14px !important;
            margin-bottom: .6rem;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        .report-detail-page .list-group-item:last-child {
            margin-bottom: 0;
        }
        .report-detail-page .list-group-item::before {
            color: var(--gl-blue) !important;
            font-weight: 800;
        }
        .report-detail-page .list-group-item strong {
            color: var(--gl-ink);
        }
    </style>
@endpush

@section('content')
<div class="report-detail-page">
    <span class="glass-blob glass-blob--1" aria-hidden="true"></span>
    <span class="glass-blob glass-blob--2" aria-hidden="true"></span>
    <span class="glass-blob glass-blob--3" aria-hidden="true"></span>

    <main class="container py-5" style="max-width: 900px;">
        <a href="{{ route('kelurahan.dashboard') }}" class="btn btn-outline-secondary mb-4">Kembali</a>
        <div class="d-flex justify-content-between gap-3 mb-4"><div><p class="text-secondary mb-1">Nomor tiket</p><h1 class="h2">{{ $report->ticket_number }}</h1></div><span class="badge text-bg-primary align-self-start fs-6">{{ $report->status->value }}</span></div>
        <div class="card border-0 shadow-sm mb-4"><div class="card-body p-4"><dl class="row mb-0">
            <dt class="col-sm-4">Warga</dt><dd class="col-sm-8">{{ $report->citizen->name }}</dd>
            <dt class="col-sm-4">RW</dt><dd class="col-sm-8">{{ $report->rt->rw->code }} — {{ $report->rt->rw->name }}</dd>
            <dt class="col-sm-4">RT</dt><dd class="col-sm-8">{{ $report->rt->code }} — {{ $report->rt->name }}</dd>
            <dt class="col-sm-4">Judul</dt><dd class="col-sm-8">{{ $report->title }}</dd>
            <dt class="col-sm-4">Deskripsi</dt><dd class="col-sm-8">{{ $report->description }}</dd>
            <dt class="col-sm-4">Tanggal laporan</dt><dd class="col-sm-8 mb-0">{{ $report->reported_at->format('d-m-Y H:i') }}</dd>
        </dl></div></div>
        @include('reports.partials.attachments', ['attachments' => $report->attachments])

        <div class="card border-0 shadow-sm"><div class="card-body p-4"><h2 class="h4">Riwayat Status</h2><ol class="list-group list-group-numbered">
            @foreach ($histories as $history)<li class="list-group-item"><strong>{{ $history->new_status->value }}</strong><span class="text-secondary small ms-2">{{ $history->created_at->format('d-m-Y H:i') }}</span>@if ($history->note)<p class="mb-0 mt-2">{{ $history->note }}</p>@endif</li>@endforeach
        </ol></div></div>
    </main>
</div>
@endsection