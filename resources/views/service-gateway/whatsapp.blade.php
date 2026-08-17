@extends('layouts.app')

@section('title', 'Lanjut ke WhatsApp — SIGAP WARGA')

@section('content')
<div class="citizen-portal min-vh-100">
    <header class="citizen-header">
        <div class="container py-3 d-flex align-items-center justify-content-between gap-3">
            <a class="citizen-brand text-decoration-none" href="{{ route('public.home') }}">
                <span class="citizen-brand-mark" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
                <span class="citizen-brand-copy"><strong>SIGAP WARGA</strong><small>{{ config('village.name') }}</small></span>
            </a>
        </div>
    </header>

    <main id="main-content" class="container py-5" style="max-width: 680px">
        <section class="card dashboard-panel-modern text-center">
            <div class="card-body p-4 p-lg-5">
                <span class="badge rounded-pill text-bg-success px-3 py-2 mb-3">Wilayah terverifikasi</span>
                <h1 class="h3 fw-bold">Lanjut ke WhatsApp SIGAP WARGA</h1>
                <p class="text-secondary mb-4">
                    Pintu layanan <strong>{{ $entryPoint->rt->code }} / {{ $entryPoint->rt->rw->code }}</strong> sudah dikenali.
                    Tekan tombol di bawah untuk membuka WhatsApp.
                </p>

                <a class="btn btn-success btn-lg w-100" href="{{ $whatsappUrl }}">
                    <i class="bi bi-whatsapp me-2" aria-hidden="true"></i>Buka WhatsApp Resmi
                </a>

                <a class="btn btn-outline-secondary w-100 mt-3" href="{{ url()->previous() }}">Kembali</a>
            </div>
        </section>
    </main>
</div>
@endsection
