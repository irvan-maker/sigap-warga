@extends('layouts.app')

@section('title', 'QR Wilayah - SIGAP WARGA')

@section('content')
@php
    $user = auth()->user();
    $isVillageContext = $user?->role === \App\Enums\UserRole::KELURAHAN;
    $backUrl = $isVillageContext ? route('kelurahan.dashboard') : route('dashboard');
@endphp

<style>
    .qr-page {
        --qr-primary: #6f2da8;
        --qr-primary-dark: #552080;
        --qr-primary-soft: #f3eafd;
        --qr-accent: #a8ff3e;
        --qr-accent-soft: #efffdc;
        --qr-text: #16121d;
        --qr-muted: #706779;
        --qr-border: #e7e1eb;
        --qr-bg: #f7f6fa;
        min-height: calc(100vh - 70px);
        background:
            radial-gradient(circle at 85% 10%, rgba(111, 45, 168, .08), transparent 28rem),
            var(--qr-bg);
        padding: 38px 0 56px;
    }

    .qr-page.is-global {
        --qr-primary: #1769aa;
        --qr-primary-dark: #0f4f82;
        --qr-primary-soft: #eaf4ff;
        --qr-accent: #3bc7a1;
        --qr-accent-soft: #e5fbf4;
    }

    .qr-container {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }

    .qr-hero {
        position: relative;
        overflow: hidden;
        border-radius: 24px;
        padding: 30px 32px;
        color: #fff;
        background:
            radial-gradient(circle at 84% 20%, rgba(255,255,255,.18), transparent 17rem),
            linear-gradient(135deg, var(--qr-primary-dark), var(--qr-primary));
        box-shadow: 0 18px 45px rgba(52, 27, 78, .14);
        margin-bottom: 24px;
    }

    .qr-hero::after {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
        opacity: .32;
        background-image: radial-gradient(rgba(255,255,255,.35) .8px, transparent .8px);
        background-size: 18px 18px;
    }

    .qr-hero-content {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: space-between;
        gap: 24px;
        align-items: flex-start;
    }

    .qr-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 999px;
        padding: 7px 12px;
        margin-bottom: 14px;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--qr-accent);
        background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.15);
    }

    .qr-hero h1 {
        font-size: clamp(30px, 4vw, 46px);
        line-height: 1.05;
        font-weight: 800;
        margin: 0 0 10px;
    }

    .qr-hero p {
        max-width: 760px;
        margin: 0;
        color: rgba(255,255,255,.82);
        font-size: 15px;
        line-height: 1.65;
    }

    .qr-back {
        white-space: nowrap;
        border: 1px solid rgba(255,255,255,.35);
        background: rgba(255,255,255,.1);
        color: #fff;
        border-radius: 12px;
        padding: 10px 15px;
        font-weight: 700;
        text-decoration: none;
    }

    .qr-back:hover {
        color: #fff;
        background: rgba(255,255,255,.18);
    }

    .qr-summary {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
        margin-bottom: 24px;
    }

    .qr-summary-item {
        border: 1px solid var(--qr-border);
        background: #fff;
        border-radius: 16px;
        padding: 16px 18px;
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .qr-summary-icon {
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        background: var(--qr-accent-soft);
        color: var(--qr-primary);
        font-size: 20px;
    }

    .qr-summary-copy strong {
        display: block;
        color: var(--qr-text);
        font-size: 14px;
    }

    .qr-summary-copy span {
        color: var(--qr-muted);
        font-size: 12px;
    }

    .qr-flash {
        border: 0;
        border-radius: 14px;
        padding: 14px 16px;
        margin-bottom: 18px;
    }

    .qr-issued {
        background: #fff;
        border: 1px solid rgba(82, 176, 98, .55);
        border-radius: 22px;
        padding: 28px;
        box-shadow: 0 15px 40px rgba(54, 42, 68, .08);
        margin-bottom: 24px;
    }

    .qr-issued-grid {
        display: grid;
        grid-template-columns: 310px 1fr;
        gap: 28px;
        align-items: center;
    }

    .qr-image-wrap {
        display: grid;
        place-items: center;
        min-height: 310px;
        padding: 12px;
        border-radius: 18px;
        background: #fff;
        border: 1px dashed #d9d1df;
    }

    .qr-image-wrap img {
        width: min(100%, 300px);
        height: auto;
    }

    .qr-new-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 6px 10px;
        margin-bottom: 10px;
        font-size: 11px;
        font-weight: 800;
        color: #185c33;
        background: #dcf7e4;
    }

    .qr-issued h2 {
        margin: 0 0 8px;
        font-size: 25px;
        color: var(--qr-text);
    }

    .qr-issued p {
        color: var(--qr-muted);
    }

    .qr-token-note {
        border: 1px solid #f1d17b;
        background: #fff4cf;
        color: #6d5012;
        border-radius: 12px;
        padding: 13px 15px;
        margin: 16px 0;
    }

    .qr-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .qr-btn-primary {
        border: 0;
        background: var(--qr-primary);
        color: #fff;
        border-radius: 11px;
        padding: 10px 15px;
        font-weight: 700;
        text-decoration: none;
    }

    .qr-btn-primary:hover {
        color: #fff;
        background: var(--qr-primary-dark);
    }

    .qr-btn-outline {
        border: 1px solid #cfc7d6;
        background: #fff;
        color: var(--qr-text);
        border-radius: 11px;
        padding: 9px 14px;
        font-weight: 700;
    }

    .qr-grid {
        display: grid;
        grid-template-columns: minmax(320px, .8fr) minmax(0, 1.2fr);
        gap: 22px;
        align-items: start;
    }

    .qr-card {
        background: #fff;
        border: 1px solid var(--qr-border);
        border-radius: 20px;
        box-shadow: 0 12px 34px rgba(54, 42, 68, .06);
        overflow: hidden;
    }

    .qr-card-head {
        padding: 22px 24px 14px;
        border-bottom: 1px solid #f0ecf2;
    }

    .qr-card-head h2 {
        margin: 0 0 5px;
        color: var(--qr-text);
        font-size: 21px;
        font-weight: 800;
    }

    .qr-card-head p {
        margin: 0;
        color: var(--qr-muted);
        font-size: 13px;
        line-height: 1.55;
    }

    .qr-card-body {
        padding: 22px 24px 24px;
    }

    .qr-form-label {
        display: block;
        margin-bottom: 7px;
        font-size: 13px;
        font-weight: 800;
        color: var(--qr-text);
    }

    .qr-form-control {
        width: 100%;
        min-height: 46px;
        border: 1px solid #dcd5e1;
        border-radius: 11px;
        padding: 10px 12px;
        background: #fff;
        color: var(--qr-text);
        outline: 0;
    }

    .qr-form-control:focus {
        border-color: var(--qr-primary);
        box-shadow: 0 0 0 3px rgba(111,45,168,.1);
    }

    .qr-form-group {
        margin-bottom: 17px;
    }

    .qr-empty-note {
        border-radius: 12px;
        padding: 12px 14px;
        margin-bottom: 16px;
        background: #f7f4f9;
        color: var(--qr-muted);
        font-size: 12px;
    }

    .qr-table-wrap {
        overflow-x: auto;
    }

    .qr-table {
        width: 100%;
        border-collapse: collapse;
    }

    .qr-table th {
        padding: 13px 12px;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--qr-muted);
        background: #faf8fb;
        border-bottom: 1px solid var(--qr-border);
    }

    .qr-table td {
        padding: 15px 12px;
        color: var(--qr-text);
        border-bottom: 1px solid #eee9f0;
        vertical-align: middle;
    }

    .qr-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .qr-status {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 5px 9px;
        font-size: 11px;
        font-weight: 800;
    }

    .qr-status.active {
        color: #185c33;
        background: #dcf7e4;
    }

    .qr-status.inactive {
        color: #625a67;
        background: #eeeaf0;
    }

    .qr-audit {
        display: inline-flex;
        margin-top: 5px;
        border-radius: 999px;
        padding: 5px 8px;
        color: #76550a;
        background: #fff1bf;
        font-size: 10px;
        font-weight: 700;
    }

    .qr-revoke {
        border: 1px solid #e88791;
        background: #fff;
        color: #b52234;
        border-radius: 9px;
        padding: 7px 10px;
        font-size: 12px;
        font-weight: 700;
    }

    .qr-revoke:hover {
        background: #fff1f3;
    }

    @media (max-width: 900px) {
        .qr-summary {
            grid-template-columns: 1fr;
        }

        .qr-grid,
        .qr-issued-grid {
            grid-template-columns: 1fr;
        }

        .qr-hero-content {
            flex-direction: column;
        }
    }

    @media print {
        body * {
            visibility: hidden !important;
        }

        .qr-issued,
        .qr-issued * {
            visibility: visible !important;
        }

        .qr-issued {
            position: absolute;
            inset: 0 auto auto 0;
            width: 100%;
            border: 0;
            box-shadow: none;
        }

        .qr-actions,
        .qr-token-note {
            display: none !important;
        }
    }
</style>

<main id="main-content" class="qr-page {{ $isVillageContext ? 'is-village' : 'is-global' }}">
    <div class="qr-container">

        <section class="qr-hero">
            <div class="qr-hero-content">
                <div>
                    <span class="qr-eyebrow">
                        <i class="bi bi-qr-code-scan"></i>
                        {{ $isVillageContext ? 'Desa Curug Sangereng · Pilot' : 'SIGAP WARGA · Pilot' }}
                    </span>

                    <h1>{{ $isVillageContext ? 'QR Wilayah' : 'QR Layanan Warga' }}</h1>

                    <p>
                        {{ $isVillageContext
                            ? 'Kelola QR resmi setiap RT Desa Curug Sangereng. Satu RT hanya memiliki satu QR aktif yang menjadi pintu masuk layanan warga.'
                            : 'Kelola QR layanan wilayah. Setiap RT hanya boleh memiliki satu QR aktif yang membuka portal verifikasi layanan SIGAP WARGA.' }}
                    </p>
                </div>

                <a class="qr-back" href="{{ $backUrl }}">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </section>

        <section class="qr-summary">
            <div class="qr-summary-item">
                <span class="qr-summary-icon"><i class="bi bi-geo-alt"></i></span>
                <span class="qr-summary-copy">
                    <strong>QR berbasis wilayah</strong>
                    <span>Setiap QR terikat ke RT dan RW yang benar.</span>
                </span>
            </div>

            <div class="qr-summary-item">
                <span class="qr-summary-icon"><i class="bi bi-shield-check"></i></span>
                <span class="qr-summary-copy">
                    <strong>Satu RT, satu QR aktif</strong>
                    <span>QR lama harus dinonaktifkan sebelum membuat pengganti.</span>
                </span>
            </div>

            <div class="qr-summary-item">
                <span class="qr-summary-icon"><i class="bi bi-printer"></i></span>
                <span class="qr-summary-copy">
                    <strong>Siap cetak</strong>
                    <span>QR baru dapat langsung diuji dan dicetak.</span>
                </span>
            </div>
        </section>

        @if (session('status'))
            <div class="alert alert-success qr-flash">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger qr-flash">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @isset($issuedEntryPoint)
            <section class="qr-issued">
                <div class="qr-issued-grid">
                    <div class="qr-image-wrap">
                        <img
                            src="{{ $qrDataUri }}"
                            width="300"
                            height="300"
                            alt="QR layanan RT {{ $issuedEntryPoint->rt?->code ?? '-' }}"
                        >
                    </div>

                    <div>
                        <span class="qr-new-badge">
                            <i class="bi bi-check-circle me-1"></i>QR BARU
                        </span>

                        <h2>
                            {{ $issuedEntryPoint->label ?: 'Pintu layanan' }}
                            —
                            RW {{ $issuedEntryPoint->rt?->rw?->code ?? '-' }}
                            / RT {{ $issuedEntryPoint->rt?->code ?? '-' }}
                        </h2>

                        <p>
                            Uji dengan kamera ponsel. QR membuka halaman wilayah,
                            kemudian warga melanjutkan melalui pintu layanan SIGAP WARGA.
                        </p>

                        <div class="qr-token-note">
                            <strong>Simpan atau cetak sekarang.</strong>
                            Tautan bertoken hanya ditampilkan satu kali dan token asli tidak disimpan di basis data.
                        </div>

                        <div class="qr-actions">
                            <a class="qr-btn-primary" href="{{ $gatewayUrl }}" target="_blank" rel="noopener">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Uji halaman QR
                            </a>

                            <button class="qr-btn-outline" type="button" onclick="window.print()">
                                <i class="bi bi-printer me-1"></i>Cetak QR
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        @endisset

        <div class="qr-grid">
            <section class="qr-card">
                <div class="qr-card-head">
                    <h2>Terbitkan QR Wilayah</h2>
                    <p>Pilih RT yang belum memiliki QR aktif, lalu beri label sesuai lokasi pemasangan QR.</p>
                </div>

                <div class="qr-card-body">
                    @if ($rts->isEmpty())
                        <div class="qr-empty-note">
                            Semua RT aktif sudah memiliki QR aktif atau belum ada RT aktif yang tersedia.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.service-entry-points.store') }}">
                        @csrf

                        <div class="qr-form-group">
                            <label class="qr-form-label" for="rt_id">Wilayah RT</label>

                            <select class="qr-form-control" id="rt_id" name="rt_id" required>
                                <option value="">Pilih wilayah</option>

                                @foreach ($rts as $rt)
                                    <option
                                        value="{{ $rt->id }}"
                                        @selected((string) old('rt_id') === (string) $rt->id)
                                    >
                                        RW {{ $rt->rw?->code ?? '-' }}
                                        / RT {{ $rt->code }}
                                        — {{ $rt->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="qr-form-group">
                            <label class="qr-form-label" for="label">Label lokasi</label>

                            <input
                                class="qr-form-control"
                                id="label"
                                name="label"
                                maxlength="100"
                                value="{{ old('label') }}"
                                placeholder="Contoh: Balai Warga RT 001"
                            >
                        </div>

                        <button class="qr-btn-primary" type="submit" @disabled($rts->isEmpty())>
                            <i class="bi bi-qr-code me-1"></i>Buat QR Baru
                        </button>
                    </form>
                </div>
            </section>

            <section class="qr-card">
                <div class="qr-card-head">
                    <h2>Daftar QR Terbit</h2>
                    <p>Pantau QR aktif maupun QR yang sudah dinonaktifkan.</p>
                </div>

                <div class="qr-card-body pt-0">
                    <div class="qr-table-wrap">
                        <table class="qr-table">
                            <thead>
                                <tr>
                                    <th>Wilayah</th>
                                    <th>Label</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($entryPoints as $entryPoint)
                                    <tr>
                                        <td>
                                            <strong>RW {{ $entryPoint->rt?->rw?->code ?? '-' }}</strong><br>
                                            <span class="text-secondary">RT {{ $entryPoint->rt?->code ?? '-' }}</span>
                                        </td>

                                        <td>{{ $entryPoint->label ?: '—' }}</td>

                                        <td>
                                            <span class="qr-status {{ $entryPoint->isAvailable() ? 'active' : 'inactive' }}">
                                                {{ $entryPoint->isAvailable() ? 'Aktif' : 'Nonaktif' }}
                                            </span>

                                            @if(
                                                $entryPoint->isAvailable()
                                                && (int) ($entryPoint->rt?->active_service_entry_points_count ?? 0) > 1
                                            )
                                                <br>
                                                <span class="qr-audit">
                                                    Audit:
                                                    {{ $entryPoint->rt->active_service_entry_points_count }}
                                                    QR aktif lama
                                                </span>
                                            @endif
                                        </td>

                                        <td class="text-end">
                                            @if ($entryPoint->isAvailable())
                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.service-entry-points.revoke', $entryPoint) }}"
                                                    onsubmit="return confirm('Nonaktifkan QR ini? QR cetak lama tidak akan berfungsi.')"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    <button class="qr-revoke" type="submit">
                                                        Nonaktifkan
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-secondary py-4">
                                            Belum ada QR yang diterbitkan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>

    </div>
</main>
@endsection
