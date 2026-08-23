@extends('layouts.app')

@section('title', 'Pintu Layanan Resmi — SIGAP WARGA')

@section('content')
<div class="citizen-portal min-vh-100">
    <header class="citizen-header"><div class="container py-3 d-flex align-items-center justify-content-between gap-3"><a class="citizen-brand text-decoration-none" href="{{ route('public.home') }}"><span class="citizen-brand-mark" aria-hidden="true"><i class="bi bi-shield-check"></i></span><span class="citizen-brand-copy"><strong>SIGAP WARGA</strong><small>{{ config('village.name') }}</small></span></a><a class="small text-decoration-none" href="{{ route('public.home') }}">Portal warga</a></div></header>

    <main id="main-content" class="container py-4 py-lg-5" style="max-width: 820px">
        <nav class="dashboard-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('public.home') }}">Portal warga</a> <span aria-hidden="true">/</span> Verifikasi QR</nav>

        <header class="text-center mb-4">
            <span class="badge rounded-pill text-bg-success px-3 py-2 mb-3"><i class="bi bi-patch-check-fill me-1" aria-hidden="true"></i>QR resmi dan aktif</span>
            <h1 class="h2 fw-bold">Pintu Layanan {{ $entryPoint->rt->name }} / {{ $entryPoint->rt->rw->name }}</h1>
            <p class="text-secondary mb-0">Periksa identitas wilayah di bawah sebelum melanjutkan ke WhatsApp.</p>
        </header>

        <section class="card dashboard-panel-modern overflow-hidden">
            <div class="card-body p-4 p-lg-5">
                <div class="row g-4 align-items-start">
                    <div class="col-md-5">
                        <div class="priority-panel h-100">
                            <span class="section-eyebrow">Identitas pintu masuk</span>
                            <h2 class="h4 fw-bold mt-2 mb-3">{{ $entryPoint->rt->code }} / {{ $entryPoint->rt->rw->code }}</h2>
                            <dl class="small mb-0">
                                <div class="d-flex justify-content-between gap-3 py-2 border-bottom"><dt class="text-secondary fw-normal">Kelurahan</dt><dd class="fw-semibold text-end mb-0">{{ config('village.name') }}</dd></div>
                                <div class="d-flex justify-content-between gap-3 py-2 border-bottom"><dt class="text-secondary fw-normal">RT</dt><dd class="fw-semibold text-end mb-0">{{ $entryPoint->rt->name }}</dd></div>
                                <div class="d-flex justify-content-between gap-3 py-2 border-bottom"><dt class="text-secondary fw-normal">RW</dt><dd class="fw-semibold text-end mb-0">{{ $entryPoint->rt->rw->name }}</dd></div>
                                @if($entryPoint->label)<div class="d-flex justify-content-between gap-3 py-2"><dt class="text-secondary fw-normal">Lokasi QR</dt><dd class="fw-semibold text-end mb-0">{{ $entryPoint->label }}</dd></div>@endif
                            </dl>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <p class="section-eyebrow mb-1">Laporan Cepat · Pilot Aktif</p>
                        <h2 class="h4 fw-bold">Mulai melalui WhatsApp</h2>
                        <p class="text-secondary">Setelah pesan pembuka dikirim, SIGAP WARGA akan menyapa. Jelaskan kejadian dan lokasi dengan bahasa sehari-hari.</p>

                        <form method="POST" action="{{ route('service-gateway.whatsapp', ['entryToken' => $entryToken]) }}">
                            @csrf
                            <div class="form-check text-start mb-3">
                                <input class="form-check-input @error('privacy_acknowledged') is-invalid @enderror" type="checkbox" value="1" id="privacy_acknowledged" name="privacy_acknowledged" required>
                                <label class="form-check-label small" for="privacy_acknowledged">Saya telah membaca <a href="{{ route('public.privacy') }}" target="_blank" rel="noopener noreferrer">informasi privasi</a> dan akan mengirimkan data yang diperlukan untuk penanganan laporan.</label>
                                @error('privacy_acknowledged')<div class="invalid-feedback">Baca dan setujui informasi penggunaan data sebelum melanjutkan.</div>@enderror
                            </div>
                            <button class="btn btn-success btn-lg w-100" type="submit"><i class="bi bi-whatsapp me-2" aria-hidden="true"></i>Buka WhatsApp Resmi</button>
                        </form>

                        <a class="btn btn-outline-primary w-100 mt-3" href="{{ route('tracking.index') }}"><i class="bi bi-search me-2" aria-hidden="true"></i>Cek Status Laporan</a>
                    </div>
                </div>
            </div>
        </section>

        <aside class="alert alert-warning border-0 shadow-sm mt-4" role="note">
            <div class="d-flex gap-3"><i class="bi bi-shield-exclamation fs-4" aria-hidden="true"></i><div><strong>Layanan ini gratis.</strong><div class="small mt-1">SIGAP WARGA tidak pernah meminta OTP, PIN, password, atau transfer uang. Wilayah ini merupakan pintu layanan; lokasi kejadian dapat berbeda dan akan diklarifikasi bila diperlukan.</div></div></div>
        </aside>

        <div class="d-flex flex-wrap justify-content-center gap-2 mt-4" aria-label="Status modul lain">
            @if(config('modules.census.enabled'))<span class="module-pill module-pill-prototype">Sensus · Prototype</span>@endif
            @if(config('modules.posyandu.enabled'))<span class="module-pill module-pill-prototype">Posyandu · Prototype</span>@endif
            @if(config('modules.letters.enabled'))<span class="module-pill module-pill-prototype">Persuratan · Prototype</span>@endif
            <span class="module-pill module-pill-disabled">Darurat · Safety prototype</span>
        </div>
    </main>
</div>
@endsection
