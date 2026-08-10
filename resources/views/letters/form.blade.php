@extends('layouts.app')
@section('title',$letter->exists?'Edit Draft Surat':'Buat Pengajuan Surat')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ==========================================================
           SIGAP WARGA — Form Surat: Glassmorphism (blue accent)
           Sama persis dengan sistem desain Dashboard RT / Kelurahan
           ========================================================== */
        .letter-form-page {
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
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            color: var(--gl-ink);
            background: linear-gradient(165deg, #eef2fc 0%, #e6ecfb 45%, #eef4fb 100%);
        }

        .letter-form-page h1,
        .letter-form-page h2,
        .letter-form-page label {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
        }

        /* floating blurred color blobs = the "glass" needs something to refract */
        .letter-form-page .glass-blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            z-index: 0;
            pointer-events: none;
        }
        .letter-form-page .glass-blob--1 {
            width: 420px; height: 420px;
            top: -12%; left: -10%;
            background: radial-gradient(circle, rgba(43,92,255,0.5), transparent 70%);
        }
        .letter-form-page .glass-blob--2 {
            width: 380px; height: 380px;
            top: 30%; right: -14%;
            background: radial-gradient(circle, rgba(124,92,255,0.4), transparent 70%);
        }
        .letter-form-page .glass-blob--3 {
            width: 420px; height: 420px;
            bottom: -18%; left: 20%;
            background: radial-gradient(circle, rgba(52,209,200,0.35), transparent 70%);
        }

        .letter-form-page main.container {
            position: relative;
            z-index: 1;
        }

        /* ---------- Breadcrumb ---------- */
        .letter-form-page .breadcrumb-item a {
            color: var(--gl-blue);
            font-weight: 600;
            text-decoration: none;
        }
        .letter-form-page .breadcrumb-item a:hover { text-decoration: underline; }
        .letter-form-page .breadcrumb-item.active { color: var(--gl-muted); }

        .letter-form-page h1 {
            font-weight: 800;
        }

        /* ---------- Alert ---------- */
        .letter-form-page .alert-danger {
            background: rgba(255, 59, 59, 0.1);
            border: 1px solid rgba(255, 59, 59, 0.3);
            color: #c72d2d;
            border-radius: 14px;
            backdrop-filter: blur(6px);
            font-weight: 600;
        }

        /* ---------- Glass form card ---------- */
        .letter-form-page .card {
            background: var(--gl-glass-bg) !important;
            backdrop-filter: blur(18px) saturate(180%);
            -webkit-backdrop-filter: blur(18px) saturate(180%);
            border: 1px solid var(--gl-glass-border) !important;
            border-radius: 20px !important;
            box-shadow: var(--gl-glass-shadow) !important;
        }

        .letter-form-page .form-label {
            font-weight: 700;
            font-size: .85rem;
            color: var(--gl-ink);
            margin-bottom: .5rem;
        }
        .letter-form-page .form-text {
            color: var(--gl-muted);
        }

        /* ---------- Forms ---------- */
        .letter-form-page .form-select,
        .letter-form-page .form-control {
            background: rgba(255, 255, 255, 0.6) !important;
            border: 1px solid rgba(43, 92, 255, 0.25) !important;
            border-radius: 12px !important;
            backdrop-filter: blur(6px);
            font-weight: 500;
            padding: .6rem .85rem;
        }
        .letter-form-page .form-select:focus,
        .letter-form-page .form-control:focus {
            border-color: var(--gl-blue) !important;
            box-shadow: 0 0 0 3px rgba(43, 92, 255, 0.15) !important;
            background: rgba(255, 255, 255, 0.85) !important;
        }

        /* ---------- Buttons ---------- */
        .letter-form-page .btn {
            border-radius: 12px;
            font-weight: 700;
            padding: .6rem 1.4rem;
        }
        .letter-form-page .btn-primary {
            background: linear-gradient(135deg, var(--gl-blue), var(--gl-blue-light));
            border: none;
            box-shadow: 0 8px 22px rgba(43, 92, 255, 0.35);
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .letter-form-page .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(43, 92, 255, 0.45);
        }
        .letter-form-page .btn-outline-secondary {
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(28, 35, 64, 0.2);
            color: var(--gl-ink);
            backdrop-filter: blur(6px);
        }
        .letter-form-page .btn-outline-secondary:hover {
            background: rgba(255, 255, 255, 0.8);
        }
    </style>
@endpush

@section('content')
<div class="letter-form-page">
    <span class="glass-blob glass-blob--1" aria-hidden="true"></span>
    <span class="glass-blob glass-blob--2" aria-hidden="true"></span>
    <span class="glass-blob glass-blob--3" aria-hidden="true"></span>

    <main class="container py-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('rt.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('rt.letters.index') }}">Surat</a></li>
                <li class="breadcrumb-item active">{{ $letter->exists?'Edit':'Buat' }}</li>
            </ol>
        </nav>
        <h1 class="h3 mb-3">{{ $letter->exists?'Edit Draft Surat':'Buat Pengajuan Surat' }}</h1>
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ $letter->exists?route('rt.letters.update',$letter):route('rt.letters.store') }}" class="card card-body p-4 p-md-5 shadow-sm">
            @csrf @if($letter->exists)@method('PUT')@endif
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Warga
                        <select name="citizen_id" class="form-select" required>
                            <option value="">Pilih warga RT</option>
                            @foreach($citizens as $citizen)
                                <option value="{{ $citizen->id }}" @selected(old('citizen_id',$letter->citizen_id)==$citizen->id)>{{ $citizen->name }} - {{ $citizen->nik?:'tanpa NIK' }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="form-text">NIK, alamat, dan KK dibaca langsung dari Master Data Warga.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Jenis Surat
                        <select name="letter_type" class="form-select" required>
                            @foreach(\App\Enums\LetterType::cases() as $type)
                                <option value="{{ $type->value }}" @selected(old('letter_type',$letter->letter_type?->value)===$type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div class="col-12">
                    <label class="form-label">Tujuan
                        <textarea name="purpose" rows="4" class="form-control" required>{{ old('purpose',$letter->purpose) }}</textarea>
                    </label>
                </div>
                <div class="col-12">
                    <label class="form-label">Catatan
                        <textarea name="notes" rows="3" class="form-control">{{ old('notes',$letter->notes) }}</textarea>
                    </label>
                </div>
            </div>
            <div class="mt-4">
                <button class="btn btn-primary">Simpan Draft</button>
                <a href="{{ route('rt.letters.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </main>
</div>
@endsection