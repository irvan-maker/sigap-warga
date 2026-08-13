@extends('layouts.app')

@section('title', 'Pintu Layanan — SIGAP WARGA')

@section('content')
<main id="main-content" class="container py-5" style="max-width: 760px">
    <header class="text-center mb-4">
        <p class="text-uppercase text-primary fw-semibold mb-2">SIGAP WARGA</p>
        <h1 class="h2">Pintu Layanan {{ $entryPoint->rt->name }} / {{ $entryPoint->rt->rw->name }}</h1>
        @if($entryPoint->label)
            <p class="text-secondary mb-0">{{ $entryPoint->label }}</p>
        @endif
    </header>

    <section class="card shadow-sm border-0">
        <div class="card-body p-4">
            <span class="badge text-bg-success mb-3">Laporan Cepat · Pilot Aktif</span>
            <div class="alert alert-success" role="status">
                <strong>QR resmi dan aktif.</strong><br>
                Pintu layanan: {{ $entryPoint->rt->code }} / {{ $entryPoint->rt->rw->code }}
            </div>
            <h2 class="h5">Mulai melalui WhatsApp</h2>
            <p class="text-secondary">Setelah pesan pembuka dikirim, SIGAP WARGA akan menyapa dan Anda dapat menjelaskan laporan dengan bahasa sehari-hari.</p>

            <form method="POST" action="{{ route('service-gateway.whatsapp', ['entryToken' => $entryToken]) }}">
                @csrf
                <div class="form-check text-start mb-3">
                    <input class="form-check-input @error('privacy_acknowledged') is-invalid @enderror" type="checkbox" value="1" id="privacy_acknowledged" name="privacy_acknowledged" required>
                    <label class="form-check-label" for="privacy_acknowledged">Saya telah membaca <a href="{{ route('public.privacy') }}" target="_blank" rel="noopener noreferrer">informasi privasi</a> dan akan mengirimkan data yang diperlukan untuk penanganan laporan.</label>
                    @error('privacy_acknowledged')<div class="invalid-feedback">Baca dan setujui informasi penggunaan data sebelum melanjutkan.</div>@enderror
                </div>
                <button class="btn btn-success btn-lg w-100" type="submit"><i class="bi bi-whatsapp me-2" aria-hidden="true"></i>Buka WhatsApp</button>
            </form>

            <a class="btn btn-outline-primary btn-lg w-100 mt-3" href="{{ route('tracking.index') }}">
                <i class="bi bi-search me-2" aria-hidden="true"></i>Cek Status Laporan
            </a>

            <hr class="my-4">
            <div class="d-flex flex-wrap gap-2" aria-label="Status modul lain">
                    @if(config('modules.census.enabled'))<span class="badge text-bg-light">Sensus · Prototype</span>@endif
                    @if(config('modules.posyandu.enabled'))<span class="badge text-bg-light">Posyandu · Prototype</span>@endif
                    @if(config('modules.letters.enabled'))<span class="badge text-bg-light">Persuratan · Prototype</span>@endif
                <span class="badge text-bg-light">Darurat · Safety prototype</span>
            </div>
        </div>
    </section>

    <p class="alert alert-warning mt-4 mb-0">
        Layanan ini gratis. SIGAP WARGA tidak pernah meminta OTP, PIN, password, atau transfer uang.
        Wilayah ini merupakan pintu layanan; lokasi kejadian dapat berbeda dan akan diklarifikasi bila diperlukan.
    </p>
</main>
@endsection
