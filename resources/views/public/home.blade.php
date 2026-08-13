@extends('layouts.app')

@section('title', config('village.name').' - Portal Resmi SIGAP WARGA')

@section('content')
<div class="citizen-portal">
    <header class="citizen-header sticky-top">
        <nav class="navbar navbar-expand-lg" aria-label="Navigasi portal warga">
            <div class="container py-2">
                <a class="navbar-brand citizen-brand" href="{{ route('public.home') }}">
                    <span class="citizen-brand-mark" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
                    <span class="citizen-brand-copy"><strong>SIGAP WARGA</strong><small>{{ config('village.name') }}</small></span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#citizenNavigation" aria-controls="citizenNavigation" aria-expanded="false" aria-label="Buka navigasi">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div id="citizenNavigation" class="collapse navbar-collapse">
                    <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                        <li class="nav-item"><a class="nav-link" href="#layanan">Layanan</a></li>
                        <li class="nav-item"><a class="nav-link" href="#cara-kerja">Cara kerja</a></li>
                        <li class="nav-item"><a class="nav-link" href="#statistik">Transparansi</a></li>
                        <li class="nav-item"><a class="nav-link" href="#kontak">Kontak resmi</a></li>
                        <li class="nav-item ms-lg-2">
                            @auth
                                <a class="btn btn-primary" href="{{ route('dashboard') }}"><i class="bi bi-grid me-1" aria-hidden="true"></i>Masuk Dashboard</a>
                            @else
                                <a class="btn btn-outline-primary" href="{{ route('login') }}">Login Petugas</a>
                            @endauth
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main id="main-content">
        <section class="citizen-hero">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-7">
                        <span class="citizen-hero-kicker"><i class="bi bi-patch-check-fill" aria-hidden="true"></i>Portal layanan resmi {{ config('village.name') }}</span>
                        <h1>Lapor lebih mudah, penanganan tetap mengikuti wilayah.</h1>
                        <p class="lead mt-4">Gunakan QR resmi di lingkungan RT untuk memulai laporan cepat melalui WhatsApp. Setiap laporan tercatat dan dapat dilacak tanpa membuat akun.</p>
                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <a class="btn btn-light btn-lg" href="{{ route('tracking.index') }}"><i class="bi bi-search me-2" aria-hidden="true"></i>Lacak Laporan</a>
                            <a class="btn btn-outline-light btn-lg" href="#cara-kerja">Lihat Cara Kerja</a>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <aside class="citizen-trust-card" aria-label="Jaminan keamanan layanan">
                            <div class="citizen-trust-item"><span class="citizen-trust-icon"><i class="bi bi-qr-code-scan" aria-hidden="true"></i></span><div><strong>1 RT, 1 QR resmi</strong><small class="d-block text-white-50 mt-1">Portal menampilkan wilayah sebelum warga diarahkan ke WhatsApp.</small></div></div>
                            <div class="citizen-trust-item"><span class="citizen-trust-icon"><i class="bi bi-cash-coin" aria-hidden="true"></i></span><div><strong>Tidak dipungut biaya</strong><small class="d-block text-white-50 mt-1">Petugas tidak pernah meminta OTP, PIN, kata sandi, atau transfer uang.</small></div></div>
                            <div class="citizen-trust-item"><span class="citizen-trust-icon"><i class="bi bi-diagram-3" aria-hidden="true"></i></span><div><strong>Hierarki tetap terjaga</strong><small class="d-block text-white-50 mt-1">RT menangani lebih dulu, lalu meneruskan ke RW atau kelurahan bila diperlukan.</small></div></div>
                        </aside>
                    </div>
                </div>
            </div>
        </section>

        <section id="layanan" class="citizen-section" aria-labelledby="services-heading">
            <div class="container">
                <p class="section-eyebrow mb-2">Layanan warga</p>
                <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
                    <div><h2 id="services-heading" class="section-title mb-2">Pilih kebutuhan Anda</h2><p class="text-secondary mb-0">Akses sederhana dengan petunjuk yang jelas di setiap tahap.</p></div>
                    <span class="badge rounded-pill text-bg-light border text-secondary px-3 py-2">Laporan cepat menjadi fokus uji lokal</span>
                </div>
                <div class="row g-4">
                    @foreach ([
                        ['bi-qr-code-scan', 'Buat Laporan Cepat', 'Scan QR resmi yang terpasang di wilayah RT Anda, periksa identitas wilayah, lalu lanjutkan ke WhatsApp.', '#cara-kerja', 'Pelajari alurnya'],
                        ['bi-search', 'Lacak Laporan', 'Masukkan nomor laporan dan nomor HP/WhatsApp untuk melihat perkembangan penanganan.', route('tracking.index'), 'Buka pelacakan'],
                        ['bi-file-earmark-check', 'Lacak Surat', 'Cek status pengajuan surat dan unduh dokumen yang sudah diterbitkan.', route('letter-tracking.index'), 'Lacak surat'],
                        ['bi-headset', 'Kontak Resmi', 'Pastikan informasi layanan, alamat kantor, dan nomor kontak berasal dari portal ini.', '#kontak', 'Lihat kontak'],
                    ] as [$icon, $title, $description, $url, $label])
                        <div class="col-sm-6 col-xl-3">
                            <a class="citizen-service-card" href="{{ $url }}">
                                <span class="citizen-service-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>
                                <h3 class="h5">{{ $title }}</h3>
                                <small>{{ $description }}</small>
                                <span class="service-link">{{ $label }} <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="cara-kerja" class="citizen-section citizen-section-soft" aria-labelledby="workflow-heading">
            <div class="container">
                <div class="row g-5 align-items-start">
                    <div class="col-lg-5">
                        <p class="section-eyebrow mb-2">Alur laporan cepat</p>
                        <h2 id="workflow-heading" class="section-title">Tiga langkah yang mudah diverifikasi</h2>
                        <p class="text-secondary">Jangan memindai QR yang tertutup stiker, rusak, atau mengarah ke domain selain domain resmi SIGAP WARGA.</p>
                        <div class="alert alert-success border-0 mt-4 mb-0"><i class="bi bi-shield-check me-2" aria-hidden="true"></i><strong>Domain resmi:</strong> {{ parse_url(config('app.url'), PHP_URL_HOST) ?: config('app.url') }}</div>
                    </div>
                    <div class="col-lg-7">
                        <div class="d-grid gap-3">
                            @foreach ([
                                ['1', 'Scan QR wilayah', 'Gunakan QR resmi yang dipasang pengurus RT. Setiap RT memiliki satu pintu masuk yang berbeda.'],
                                ['2', 'Periksa identitas portal', 'Pastikan domain, nama kelurahan, RT, dan RW sesuai sebelum menekan tombol menuju WhatsApp.'],
                                ['3', 'Tulis laporan dengan bahasa biasa', 'Sampaikan kejadian, lokasi, dan kondisi. Sistem akan mencatat laporan ke wilayah yang benar.'],
                            ] as [$number, $title, $description])
                                <div class="setup-step pb-3">
                                    <span class="setup-step-number">{{ $number }}</span>
                                    <div class="setup-step-body"><h3>{{ $title }}</h3><p class="mb-0">{{ $description }}</p></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="statistik" class="citizen-section" aria-labelledby="statistics-heading">
            <div class="container">
                <p class="section-eyebrow mb-2">Transparansi pelayanan</p>
                <h2 id="statistics-heading" class="section-title mb-4">Ringkasan layanan publik</h2>
                <div class="row g-3">
                    @foreach ([
                        ['Total laporan', $statistics['total_reports']],
                        ['Laporan selesai', $statistics['completed_reports']],
                        ['Sedang diproses', $statistics['processing_reports']],
                        ['Surat diterbitkan', $statistics['issued_letters']],
                        ['Penyelesaian', $statistics['completion_percentage'].'%'],
                    ] as [$label, $value])
                        <div class="col-6 col-lg"><div class="citizen-stat"><strong>{{ $value }}</strong><span class="small text-secondary">{{ $label }}</span></div></div>
                    @endforeach
                </div>
                <div class="card dashboard-panel-modern mt-4">
                    <div class="card-header px-4 py-3"><h3 class="h6 fw-bold mb-0">Aktivitas enam bulan terakhir</h3></div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0"><thead><tr><th>Bulan</th><th>Laporan</th><th>Pengajuan surat</th></tr></thead><tbody>@foreach($trend as $month)<tr><th scope="row">{{ $month['label'] }}</th><td>{{ $month['reports'] }}</td><td>{{ $month['letters'] }}</td></tr>@endforeach</tbody></table>
                    </div>
                </div>
            </div>
        </section>

        <section id="kontak" class="citizen-section citizen-section-soft" aria-labelledby="contact-heading">
            <div class="container"><div class="row g-5">
                <div class="col-lg-5"><p class="section-eyebrow mb-2">Bantuan resmi</p><h2 id="contact-heading" class="section-title">Kontak dan pelayanan</h2><p class="text-secondary">Gunakan informasi berikut untuk memverifikasi komunikasi yang mengatasnamakan SIGAP WARGA.</p></div>
                <div class="col-lg-7"><div class="row g-3">
                    @foreach ([
                        ['bi-clock', 'Jam pelayanan', config('village.service_hours') ?: 'Informasi belum tersedia.'],
                        ['bi-geo-alt', 'Alamat kantor', config('village.office_address') ?: 'Silakan hubungi kantor kelurahan.'],
                        ['bi-telephone', 'Telepon', config('village.contact_phone') ?: 'Belum tersedia'],
                        ['bi-envelope', 'Email', config('village.email') ?: 'Belum tersedia'],
                    ] as [$icon, $label, $value])
                        <div class="col-md-6"><div class="citizen-stat"><i class="bi {{ $icon }} text-primary me-2" aria-hidden="true"></i><strong class="d-inline fs-6">{{ $label }}</strong><p class="small text-secondary mt-2 mb-0">{{ $value }}</p></div></div>
                    @endforeach
                </div></div>
            </div></div>
        </section>
    </main>

    <footer class="bg-dark text-white py-4"><div class="container d-flex flex-wrap justify-content-between gap-2"><span>© {{ now()->year }} {{ config('village.name') }}</span><span class="text-white-50">Portal resmi SIGAP WARGA</span></div></footer>
</div>
@endsection
