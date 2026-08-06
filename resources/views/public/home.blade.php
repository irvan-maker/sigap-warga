@extends('layouts.app')

@section('title', config('village.name').' - Portal Pelayanan Publik')

@section('content')
<header class="public-header bg-white border-bottom sticky-top">
    <nav class="navbar navbar-expand-lg" aria-label="Navigasi utama">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="{{ route('public.home') }}"><i class="bi bi-buildings me-2" aria-hidden="true"></i>{{ config('village.name') }}</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav" aria-controls="publicNav" aria-expanded="false" aria-label="Buka navigasi"><span class="navbar-toggler-icon"></span></button>
            <div id="publicNav" class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <li class="nav-item"><a class="nav-link" href="#layanan">Layanan</a></li>
                    <li class="nav-item"><a class="nav-link" href="#statistik">Statistik</a></li>
                    <li class="nav-item"><a class="nav-link" href="#profil">Profil Desa</a></li>
                    <li class="nav-item"><a class="nav-link" href="#kontak">Kontak</a></li>
                    <li class="nav-item">@auth<a class="btn btn-primary" href="{{ route('dashboard') }}">Masuk Dashboard</a>@else<a class="btn btn-outline-primary" href="{{ route('login') }}">Login Petugas</a>@endauth</li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<main id="main-content">
    <section class="public-hero text-white py-5">
        <div class="container py-lg-5">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <p class="text-uppercase fw-semibold small letter-spacing">Portal resmi pelayanan desa</p>
                    <h1 class="display-4 fw-bold">Pelayanan publik yang mudah, jelas, dan transparan</h1>
                    <p class="lead text-white-75">Akses informasi dan pantau layanan {{ config('village.name') }} tanpa perlu membuat akun.</p>
                    <div class="d-flex flex-wrap gap-2"><a class="btn btn-light btn-lg" href="#layanan">Lihat Layanan</a><a class="btn btn-outline-light btn-lg" href="{{ route('tracking.index') }}">Lacak Laporan</a></div>
                </div>
                <div class="col-lg-5"><div class="hero-info rounded-4 p-4"><i class="bi bi-geo-alt fs-2" aria-hidden="true"></i><h2 class="h4 mt-3">{{ config('village.name') }}</h2><p class="mb-0">{{ collect([config('village.district'), config('village.regency'), config('village.province')])->filter()->join(', ') }}</p></div></div>
            </div>
        </div>
    </section>

    <section id="layanan" class="container py-5" aria-labelledby="layanan-title">
        <p class="section-eyebrow mb-2">Layanan utama</p><h2 id="layanan-title" class="section-title mb-4">Apa yang dapat kami bantu?</h2>
        <div class="row g-4">
            @foreach ([
                ['bi-megaphone', 'Buat Laporan', 'Sampaikan laporan melalui petugas RT atau kantor desa.', '#kontak'],
                ['bi-search', 'Lacak Laporan', 'Pantau laporan dengan nomor tiket dan nomor HP.', route('tracking.index')],
                ['bi-file-earmark-check', 'Lacak Surat', 'Cek status pengajuan dan unduh surat yang terbit.', route('letter-tracking.index')],
                ['bi-info-circle', 'Informasi Pelayanan', 'Lihat jam pelayanan dan kanal kontak resmi.', '#kontak'],
            ] as [$icon, $title, $description, $url])
                <div class="col-sm-6 col-xl-3"><a class="service-card card h-100 text-decoration-none" href="{{ $url }}"><div class="card-body p-4"><span class="icon-box mb-3"><i class="bi {{ $icon }}" aria-hidden="true"></i></span><h3 class="h5 text-body">{{ $title }}</h3><p class="text-secondary mb-0">{{ $description }}</p></div></a></div>
            @endforeach
        </div>
    </section>

    <section id="statistik" class="bg-white py-5" aria-labelledby="stats-title"><div class="container"><p class="section-eyebrow mb-2">Transparansi pelayanan</p><h2 id="stats-title" class="section-title mb-4">Statistik publik</h2>
        <div class="row g-3 mb-4">@foreach ([['Total laporan',$statistics['total_reports']],['Laporan selesai',$statistics['completed_reports']],['Sedang diproses',$statistics['processing_reports']],['Surat diterbitkan',$statistics['issued_letters']],['Penyelesaian laporan',$statistics['completion_percentage'].'%']] as [$label,$value])<div class="col-6 col-lg"><div class="card h-100"><div class="card-body"><div class="h3 fw-bold text-primary">{{ $value }}</div><div class="text-secondary small">{{ $label }}</div></div></div></div>@endforeach</div>
        <h3 class="h5">Tren layanan enam bulan terakhir</h3><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Bulan</th><th>Laporan</th><th>Pengajuan surat</th></tr></thead><tbody>@foreach($trend as $month)<tr><th scope="row">{{ $month['label'] }}</th><td>{{ $month['reports'] }}</td><td>{{ $month['letters'] }}</td></tr>@endforeach</tbody></table></div>
    </div></section>

    <section id="profil" class="container py-5"><div class="row g-5 align-items-center"><div class="col-lg-7"><p class="section-eyebrow mb-2">Profil desa</p><h2 class="section-title">Melayani warga dengan sepenuh hati</h2><p class="lead text-secondary">{{ config('village.description') }}</p></div><div class="col-lg-5"><div class="card"><div class="card-body p-4"><h3 class="h5">Wilayah administrasi</h3><p class="mb-0">{{ collect([config('village.district'), config('village.regency'), config('village.province')])->filter()->join(', ') }}</p></div></div></div></div></section>

    <section id="kontak" class="public-contact py-5"><div class="container"><h2 class="section-title mb-4">Kontak dan jam pelayanan</h2><div class="row g-4"><div class="col-md-6"><h3 class="h6"><i class="bi bi-clock me-2" aria-hidden="true"></i>Jam pelayanan</h3><p>{{ config('village.service_hours') ?: 'Informasi belum tersedia.' }}</p><h3 class="h6"><i class="bi bi-geo-alt me-2" aria-hidden="true"></i>Alamat kantor</h3><p>{{ config('village.office_address') ?: 'Silakan hubungi kantor desa untuk informasi alamat.' }}</p></div><div class="col-md-6"><h3 class="h6"><i class="bi bi-telephone me-2" aria-hidden="true"></i>Telepon</h3><p>{{ config('village.contact_phone') ?: 'Belum tersedia' }}</p><h3 class="h6"><i class="bi bi-envelope me-2" aria-hidden="true"></i>Email</h3><p>{{ config('village.email') ?: 'Belum tersedia' }}</p></div></div></div></section>
</main>
<footer class="bg-dark text-white py-4"><div class="container d-flex flex-wrap justify-content-between gap-2"><span>© {{ now()->year }} {{ config('village.name') }}</span><span>Portal SIGAP WARGA</span></div></footer>
@endsection
