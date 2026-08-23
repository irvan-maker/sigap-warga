@extends('layouts.app')

@section('title', 'Pengajuan Surat Diterima - '.config('village.name'))

@section('content')
<main id="main-content" class="container py-5" style="max-width: 720px">
    <section class="card border-0 shadow-sm text-center">
        <div class="card-body p-4 p-lg-5">
            <span class="display-5 text-success" aria-hidden="true"><i class="bi bi-check-circle"></i></span>
            <h1 class="h2 mt-3">Pengajuan berhasil diterima</h1>
            <p class="text-secondary">{{ $confirmation['type'] }}</p>
            <div class="bg-light border rounded-3 p-4 my-4">
                <div class="small text-secondary">Nomor pengajuan</div>
                <strong class="fs-4">{{ $confirmation['reference'] }}</strong>
            </div>
            <p>Simpan nomor pengajuan ini. Gunakan bersama nomor HP yang terdaftar untuk melihat status tanpa membuka data sensitif.</p>
            <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                <a class="btn btn-primary" href="{{ route('letter-tracking.index') }}">Lacak pengajuan</a>
                <a class="btn btn-outline-secondary" href="{{ route('public.letter-submissions.index') }}">Kembali ke Persuratan</a>
            </div>
        </div>
    </section>
</main>
@endsection
