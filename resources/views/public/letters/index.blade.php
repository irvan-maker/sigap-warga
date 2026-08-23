@extends('layouts.app')

@section('title', 'Pengajuan Surat - '.config('village.name'))

@section('content')
<main id="main-content" class="container py-5" style="max-width: 960px">
    <a href="{{ route('public.home') }}" class="d-inline-block mb-4"><i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke beranda</a>
    <header class="mb-4">
        <p class="section-eyebrow mb-2">Layanan Persuratan</p>
        <h1 class="h2">Ajukan surat secara daring</h1>
        <p class="text-secondary mb-0">Pilih jenis surat yang tersedia. Formulir dan persyaratan mengikuti konfigurasi resmi yang telah dipublikasikan.</p>
    </header>

    <div class="row g-4">
        @forelse ($letterTypes as $letterType)
            <div class="col-md-6">
                <article class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 d-flex flex-column">
                        <span class="page-icon mb-3" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
                        <h2 class="h5">{{ $letterType->name }}</h2>
                        <p class="text-secondary flex-grow-1">{{ $letterType->description ?: 'Keterangan layanan tersedia pada formulir pengajuan.' }}</p>
                        <small class="text-secondary mb-3">Konfigurasi version {{ $letterType->latestPublishedVersion->version }}</small>
                        <a class="btn btn-primary" href="{{ route('public.letter-submissions.create', $letterType) }}">Buka formulir <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-info">Belum ada jenis surat aktif dengan konfigurasi published.</div></div>
        @endforelse
    </div>
</main>
@endsection
