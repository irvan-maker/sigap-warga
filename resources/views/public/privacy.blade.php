@extends('layouts.app')

@section('title', 'Informasi Privasi - SIGAP WARGA')

@section('content')
<main id="main-content" class="container py-5" style="max-width: 860px">
    <a href="{{ url()->previous() }}" class="d-inline-block mb-4">← Kembali</a>
    <h1 class="h2">Informasi Privasi Layanan Laporan Cepat</h1>
    <p class="lead">Ringkasan ini menjelaskan penggunaan data pada tahap pilot SIGAP WARGA.</p>

    <section class="card border-0 shadow-sm mb-3"><div class="card-body p-4">
        <h2 class="h5">Data yang digunakan</h2>
        <p class="mb-0">Nomor WhatsApp digunakan untuk mencocokkan warga aktif. Isi laporan, waktu, wilayah pintu masuk, status, dan riwayat penanganan digunakan untuk menerima serta menindaklanjuti laporan.</p>
    </div></section>
    <section class="card border-0 shadow-sm mb-3"><div class="card-body p-4">
        <h2 class="h5">Akses dan kerahasiaan</h2>
        <p class="mb-0">Data hanya boleh diakses petugas sesuai hierarki RT, RW, dan Kelurahan. Catatan internal serta lampiran internal tidak ditampilkan pada pelacakan publik. Jangan kirim OTP, PIN, password, data rekening, atau informasi kesehatan yang tidak diperlukan.</p>
    </div></section>
    <section class="card border-0 shadow-sm mb-3"><div class="card-body p-4">
        <h2 class="h5">Penyimpanan dan hak warga</h2>
        <p class="mb-0">Data disimpan selama diperlukan untuk penanganan, evaluasi pilot, audit, dan kewajiban administrasi yang berlaku. Warga dapat meminta pemeriksaan atau koreksi data melalui RT atau kantor {{ config('village.name') }}. Kebijakan retensi final ditetapkan oleh pengelola layanan sebelum perluasan pilot.</p>
    </div></section>
    <div class="alert alert-warning mb-0">Layanan gratis. SIGAP WARGA tidak pernah meminta OTP, PIN, password, atau transfer uang.</div>
</main>
@endsection
