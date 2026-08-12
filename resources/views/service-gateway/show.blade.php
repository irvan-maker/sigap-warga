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
            <h2 class="h5">Pilih kebutuhan Anda</h2>
            <p class="text-secondary">Semua pilihan masuk melalui gateway yang sama. Aturan layanan tetap disesuaikan dengan kebutuhan warga.</p>

            <form method="POST" action="{{ route('service-gateway.whatsapp', ['entryToken' => $entryToken]) }}">
                @csrf
                <div class="row g-2 mb-4">
                    @foreach([
                        'report' => 'Laporan',
                        'information' => 'Informasi',
                        'letter' => 'Surat',
                        'aspiration' => 'Aspirasi',
                        'emergency' => 'Darurat',
                    ] as $value => $label)
                        <div class="col-6 col-md-4">
                            <input class="btn-check" type="radio" name="service" id="service-{{ $value }}" value="{{ $value }}" @checked(old('service', 'report') === $value)>
                            <label class="btn btn-outline-primary w-100" for="service-{{ $value }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>

                @error('service')
                    <p class="text-danger small">{{ $message }}</p>
                @enderror

                <button class="btn btn-success btn-lg w-100" type="submit">Lanjutkan melalui WhatsApp</button>
            </form>
        </div>
    </section>

    <p class="alert alert-warning mt-4 mb-0">
        Wilayah ini merupakan pintu layanan. Lokasi kejadian dapat berbeda dan akan diklarifikasi bila diperlukan.
    </p>
</main>
@endsection
