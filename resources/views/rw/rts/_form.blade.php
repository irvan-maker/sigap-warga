@extends('layouts.app')
@section('title','Tambah RT - SIGAP WARGA')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">
    <style>
        /* ==========================================================
           SIGAP WARGA — RW Pages: Glassmorphism (Blue Accent)
           Sama persis dengan dashboard & Kelola RT
           ========================================================== */
        .rw-dashboard,
        .rw-dashboard *,
        .rw-dashboard ::placeholder {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important;
        }

        .rw-dashboard {
            --gl-blue: #2b5cff;
            --gl-blue-dark: #1a3fd6;
            --gl-blue-light: #6d90ff;
            --gl-ink: #1c2340;
            --gl-muted: #5a6485;
            --gl-glass-bg: rgba(255, 255, 255, 0.55);
            --gl-glass-border: rgba(255, 255, 255, 0.65);
            --gl-glass-shadow: 0 8px 32px rgba(31, 60, 136, 0.14);

            position: relative;
            min-height: 100vh;
            overflow-x: hidden;
            color: var(--gl-ink);
            background: linear-gradient(165deg, #eef2fc 0%, #e6ecfb 45%, #eef4fb 100%);
        }

        .rw-dashboard .glass-blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            z-index: 0;
            pointer-events: none;
        }
        .rw-dashboard .glass-blob--1 {
            width: 440px; height: 440px;
            top: -10%; left: -8%;
            background: radial-gradient(circle, rgba(43,92,255,0.5), transparent 70%);
        }
        .rw-dashboard .glass-blob--2 {
            width: 400px; height: 400px;
            top: 22%; right: -12%;
            background: radial-gradient(circle, rgba(124,92,255,0.4), transparent 70%);
        }
        .rw-dashboard .glass-blob--3 {
            width: 480px; height: 480px;
            bottom: -15%; left: 20%;
            background: radial-gradient(circle, rgba(52,209,200,0.35), transparent 70%);
        }

        .rw-dashboard > main { position: relative; z-index: 1; }

        /* ---------- Breadcrumb ---------- */
        .rw-dashboard .breadcrumb-item a {
            color: var(--gl-blue);
            font-weight: 600;
            text-decoration: none;
        }
        .rw-dashboard .breadcrumb-item.active {
            color: var(--gl-muted);
        }

        /* ---------- Cards ---------- */
        .rw-dashboard .card {
            background: var(--gl-glass-bg) !important;
            backdrop-filter: blur(18px) saturate(180%);
            -webkit-backdrop-filter: blur(18px) saturate(180%);
            border: 1px solid var(--gl-glass-border) !important;
            border-radius: 20px !important;
            box-shadow: var(--gl-glass-shadow) !important;
        }

        /* ---------- Buttons ---------- */
        .rw-dashboard .btn {
            border-radius: 12px;
            font-weight: 700;
        }
        .rw-dashboard .btn-primary {
            background: linear-gradient(135deg, var(--gl-blue), var(--gl-blue-light));
            border: none;
            box-shadow: 0 8px 22px rgba(43, 92, 255, 0.35);
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .rw-dashboard .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(43, 92, 255, 0.45);
        }
        .rw-dashboard .btn-outline-secondary {
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(43, 92, 255, 0.25);
            color: var(--gl-ink);
            backdrop-filter: blur(6px);
        }
        .rw-dashboard .btn-outline-secondary:hover {
            background: rgba(255, 255, 255, 0.9);
        }

        /* ---------- Forms ---------- */
        .rw-dashboard .form-label {
            font-weight: 700;
            color: var(--gl-ink);
        }
        .rw-dashboard .form-select,
        .rw-dashboard .form-control {
            background: rgba(255, 255, 255, 0.6) !important;
            border: 1px solid rgba(43, 92, 255, 0.25) !important;
            border-radius: 12px !important;
            backdrop-filter: blur(6px);
            font-weight: 500;
        }
        .rw-dashboard .form-select:focus,
        .rw-dashboard .form-control:focus {
            border-color: var(--gl-blue) !important;
            box-shadow: 0 0 0 3px rgba(43, 92, 255, 0.15) !important;
            background: rgba(255, 255, 255, 0.85) !important;
        }
        .rw-dashboard .form-control.is-invalid {
            border-color: #ff5a8a !important;
        }
        .rw-dashboard .invalid-feedback {
            font-weight: 600;
        }

        .rw-dashboard hr {
            border-color: rgba(43, 92, 255, 0.15);
        }
    </style>
@endpush

@section('content')
<div class="rw-dashboard">
    <span class="glass-blob glass-blob--1" aria-hidden="true"></span>
    <span class="glass-blob glass-blob--2" aria-hidden="true"></span>
    <span class="glass-blob glass-blob--3" aria-hidden="true"></span>

    <main class="container py-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('rw.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('rw.rts.index') }}">Kelola RT</a></li>
                <li class="breadcrumb-item active">Tambah RT</li>
            </ol>
        </nav>

        <h1 class="h2 fw-bold mb-4">Tambah RT</h1>

        <div class="card border-0">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('rw.rts.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="code">Kode RT</label>
                        <input class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code',$managedRt->code??'') }}" required>
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="name">Nama RT</label>
                        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name',$managedRt->name??'') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="whatsapp_number">Nomor WhatsApp</label>
                        <input class="form-control @error('whatsapp_number') is-invalid @enderror" id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number',$managedRt->whatsapp_number??'') }}">
                        @error('whatsapp_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <hr class="my-4">

                    <button type="submit" class="btn btn-primary px-4">Simpan</button>
                </form>
            </div>
        </div>
    </main>
</div>
@endsection