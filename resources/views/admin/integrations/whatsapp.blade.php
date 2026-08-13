@extends('layouts.app')

@section('title', 'Integrasi WhatsApp - SIGAP WARGA')

@section('content')
@php
    $navigation = [
        ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bi-grid-1x2'],
        ['label' => 'Laporan', 'url' => route('admin.reports.index'), 'icon' => 'bi-inbox'],
        ['label' => 'Petugas', 'url' => route('admin.users.index'), 'icon' => 'bi-people'],
        ['label' => 'QR Wilayah', 'url' => route('admin.service-entry-points.index'), 'icon' => 'bi-qr-code'],
        ['label' => 'WhatsApp', 'url' => route('admin.whatsapp-integration.index'), 'icon' => 'bi-whatsapp', 'active' => true],
    ];
    $completion = $totalChecks > 0 ? round(($readyCount / $totalChecks) * 100) : 0;
@endphp

<div class="dashboard-workspace">
    <x-dashboard.topbar :home-url="route('dashboard')" role-label="Super Admin" context="Konfigurasi sistem" :links="$navigation" />

    <main id="main-content" class="container dashboard-main">
        <nav class="dashboard-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a> <span aria-hidden="true">/</span> Integrasi WhatsApp</nav>

        <x-dashboard.hero badge="Pusat integrasi" title="Hubungkan Meta WhatsApp dengan aman" description="Ikuti urutan ini dari atas ke bawah. SIGAP WARGA hanya menampilkan status konfigurasi; nilai rahasia tetap dimasukkan langsung melalui environment hosting." icon="bi-whatsapp">
            <x-slot:meta>
                <small class="d-block mb-1">Kesiapan konfigurasi</small>
                <strong class="h4 d-block mb-2">{{ $readyCount }} dari {{ $totalChecks }} pemeriksaan</strong>
                <div class="progress" role="progressbar" aria-label="Kesiapan integrasi WhatsApp" aria-valuenow="{{ $completion }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar bg-success" style="width: {{ $completion }}%"></div>
                </div>
            </x-slot:meta>
        </x-dashboard.hero>

        <div class="alert alert-warning border-0 shadow-sm d-flex gap-3" role="alert">
            <i class="bi bi-shield-lock fs-4" aria-hidden="true"></i>
            <div><strong>Jangan masukkan secret melalui dashboard ini.</strong><div class="small mt-1">App Secret, Access Token, dan Verify Token hanya boleh disimpan di environment hosting. Jangan kirim melalui chat, email, screenshot, Git, atau tiket dukungan.</div></div>
        </div>

        <section class="dashboard-section" aria-labelledby="configuration-status-heading">
            <x-dashboard.section-heading eyebrow="Status saat ini" title="Pemeriksaan konfigurasi" description="Status hanya menunjukkan apakah nilai tersedia dan format dasarnya benar; nilai rahasia tidak pernah ditampilkan." heading-id="configuration-status-heading"></x-dashboard.section-heading>
            <div class="integration-status-grid">
                @foreach ($checks as $check)
                    <div class="integration-status integration-status-{{ $check['ready'] ? 'ready' : 'pending' }}">
                        <span class="integration-status-icon" aria-hidden="true"><i class="bi {{ $check['ready'] ? 'bi-check-lg' : 'bi-hourglass-split' }}"></i></span>
                        <span class="integration-status-copy"><strong>{{ $check['label'] }}</strong><small>{{ $check['ready'] ? 'Siap' : 'Belum siap' }} · {{ $check['help'] }}</small></span>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="row g-4 dashboard-section">
            <div class="col-xl-7">
                <section class="card dashboard-panel-modern h-100" aria-labelledby="meta-steps-heading">
                    <div class="card-body p-4 p-lg-5">
                        <x-dashboard.section-heading eyebrow="Panduan pemula" title="Urutan pendaftaran Meta" description="Selesaikan satu langkah sebelum berpindah ke langkah berikutnya." heading-id="meta-steps-heading"></x-dashboard.section-heading>

                        @foreach ([
                            ['Buat atau pilih aplikasi bisnis', 'Buka Meta for Developers, pilih My Apps, lalu buat aplikasi bertipe Business atau gunakan aplikasi SIGAP WARGA yang sudah ada. Tambahkan produk WhatsApp.'],
                            ['Buka WhatsApp API Setup', 'Di panel WhatsApp, buka API Setup. Catat nomor uji, Phone Number ID, WABA ID, temporary token, dan versi pada contoh endpoint Graph API.'],
                            ['Pilih nomor layanan', 'Gunakan test number untuk uji teknis terbatas. Sebelum pilot publik, daftarkan nomor khusus SIGAP WARGA yang dapat menerima OTP dan dimiliki institusi.'],
                            ['Siapkan kredensial server', 'Ambil App Secret dari App Settings > Basic. Untuk pilot, buat System User token dengan izin whatsapp_business_messaging dan whatsapp_business_management.'],
                            ['Isi environment hosting', 'Masukkan semua nilai langsung di panel environment hosting. Phone Number ID berbeda dari nomor WhatsApp; Verify Token dibuat sendiri dan harus sama di server serta Meta.'],
                            ['Verifikasi callback', 'Setelah versi terbaru terpasang dan URL tidak lagi 404, buka WhatsApp > Configuration. Masukkan Callback URL di bawah dan Verify Token yang sama dengan environment server.'],
                            ['Subscribe messages', 'Setelah Verify and Save berhasil, aktifkan field messages dan pastikan aplikasi tersubscribe ke WABA. Jangan aktifkan outbound sebelum pesan masuk berhasil diterima.'],
                            ['Uji dua arah', 'Kirim pesan dari satu nomor internal. Pastikan webhook diterima sekali, queue memprosesnya, laporan masuk ke RT yang benar, lalu aktifkan balasan otomatis untuk satu uji terkendali.'],
                        ] as $index => [$title, $description])
                            <div class="setup-step">
                                <span class="setup-step-number">{{ $index + 1 }}</span>
                                <div class="setup-step-body"><h3>{{ $title }}</h3><p>{{ $description }}</p></div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="col-xl-5">
                <div class="d-grid gap-4">
                    <section class="card dashboard-panel-modern" aria-labelledby="callback-heading">
                        <div class="card-body p-4">
                            <p class="section-eyebrow mb-1">Meta Webhooks</p>
                            <h2 id="callback-heading" class="h5 fw-bold">Nilai yang dimasukkan di Meta</h2>
                            <p class="text-secondary small">Callback URL aman untuk disalin. Verify Token tidak ditampilkan karena merupakan secret.</p>
                            <label class="form-label small" for="callback-url">Callback URL</label>
                            <div class="code-value">
                                <code id="callback-url">{{ $callbackUrl }}</code>
                                <button class="btn btn-sm btn-outline-primary" type="button" data-copy-target="callback-url"><i class="bi bi-copy me-1" aria-hidden="true"></i>Salin</button>
                            </div>
                            <dl class="row small mt-4 mb-0">
                                <dt class="col-5 text-secondary fw-normal">Verify Token</dt><dd class="col-7 fw-semibold">Sama dengan environment server</dd>
                                <dt class="col-5 text-secondary fw-normal">Subscribe field</dt><dd class="col-7 fw-semibold">messages</dd>
                                <dt class="col-5 text-secondary fw-normal">Namespace</dt><dd class="col-7 fw-semibold text-break">{{ $sourceNamespace }}</dd>
                                <dt class="col-5 text-secondary fw-normal">Graph version</dt><dd class="col-7 fw-semibold">{{ $graphVersion ?: 'Belum diisi' }}</dd>
                            </dl>
                        </div>
                    </section>

                    <section class="card dashboard-panel-modern" aria-labelledby="mapping-heading">
                        <div class="card-body p-4">
                            <p class="section-eyebrow mb-1">Pemetaan istilah</p>
                            <h2 id="mapping-heading" class="h5 fw-bold mb-3">Meta → environment server</h2>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead><tr><th>Di Meta</th><th>Di server</th></tr></thead>
                                    <tbody>
                                        <tr><td>App Secret</td><td><code>WHATSAPP_APP_SECRET</code></td></tr>
                                        <tr><td>Phone Number ID</td><td><code>WHATSAPP_PHONE_NUMBER_ID</code></td></tr>
                                        <tr><td>WhatsApp Business Account ID</td><td><code>WHATSAPP_WABA_ID</code></td></tr>
                                        <tr><td>Access Token</td><td><code>WHATSAPP_ACCESS_TOKEN</code></td></tr>
                                        <tr><td>Versi pada endpoint</td><td><code>WHATSAPP_GRAPH_VERSION</code></td></tr>
                                        <tr><td>Nomor bisnis</td><td><code>WHATSAPP_PUBLIC_NUMBER</code></td></tr>
                                        <tr><td>Dibuat sendiri</td><td><code>WHATSAPP_WEBHOOK_VERIFY_TOKEN</code></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="priority-panel" aria-labelledby="stop-heading">
                        <h2 id="stop-heading" class="h6 fw-bold"><i class="bi bi-sign-stop me-2 text-danger" aria-hidden="true"></i>Kapan harus berhenti?</h2>
                        <p class="small text-secondary mb-0">Jangan lanjut ke warga nyata jika callback masih 404, signature valid ditolak, signature salah diterima, worker tidak persisten, pesan ganda membuat laporan ganda, atau <code>pilot:readiness --public</code> belum lulus.</p>
                    </section>
                </div>
            </div>
        </div>

        <div class="visually-hidden" role="status" aria-live="polite" data-copy-status></div>
    </main>
</div>
@endsection
