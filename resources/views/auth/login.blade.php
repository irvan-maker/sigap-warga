@extends('layouts.app')

@section('title', 'Login - SIGAP WARGA')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .sigap-auth {
            --nb-ink: #14171a;
            --nb-purple: #6b3fa0;
            --nb-purple-dark: #4a2b70;
            --nb-green: #8dc63f;
            --nb-yellow: #ffd400;
            --nb-red: #ff3b3b;

            min-height: 100vh;
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--nb-ink);
            position: relative;
            overflow: hidden;
            background-image: url('{{ asset('images/login-bg.jpg') }}');
            background-size: cover;
            background-position: center;
        }

        /* overlay gelap keunguan supaya foto tidak mengganggu keterbacaan card */
        .sigap-auth::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(30,15,50,0.72) 0%, rgba(20,17,26,0.8) 100%);
            z-index: 0;
        }

        .sigap-auth .auth-card {
            position: relative;
            z-index: 1;
            background: #ffffff !important;
            border: 4px solid var(--nb-ink) !important;
            border-radius: 0 !important;
            box-shadow: 10px 10px 0 var(--nb-green) !important;
        }

        .sigap-auth .auth-logo {
            height: 56px;
            width: auto;
        }

        .sigap-auth .form-label {
            font-weight: 800;
            text-transform: uppercase;
            font-size: .72rem;
            letter-spacing: .06em;
        }

        .sigap-auth .form-control {
            border: 2px solid var(--nb-ink) !important;
            border-radius: 0 !important;
            box-shadow: none;
            font-weight: 600;
            padding: .65rem .85rem;
        }
        .sigap-auth .form-control:focus {
            border-color: var(--nb-purple) !important;
            box-shadow: 4px 4px 0 var(--nb-ink) !important;
        }
        .sigap-auth .form-control.is-invalid {
            border-color: var(--nb-red) !important;
            box-shadow: 4px 4px 0 var(--nb-red) !important;
        }
        .sigap-auth .invalid-feedback {
            font-weight: 700;
            color: var(--nb-red);
        }

        .sigap-auth .btn-primary {
            background: var(--nb-purple);
            border: 2px solid var(--nb-ink);
            border-radius: 0;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: .7rem 1rem;
            box-shadow: 5px 5px 0 var(--nb-ink);
            transition: transform .12s ease, box-shadow .12s ease;
        }
        .sigap-auth .btn-primary:hover {
            background: var(--nb-purple-dark);
            transform: translate(-2px, -2px);
            box-shadow: 7px 7px 0 var(--nb-ink);
        }
        .sigap-auth .btn-primary:active {
            transform: translate(3px, 3px);
            box-shadow: 0 0 0 var(--nb-ink);
        }
    </style>
@endpush

@section('content')
    <main class="sigap-auth d-flex align-items-center justify-content-center py-5">
        <div class="card auth-card border-0 w-100 mx-3" style="max-width: 420px;">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <img src="{{ asset('images/logo-curug-sangereng.png') }}" alt="Curug Sangereng" class="auth-logo mb-3">
                    <p class="text-secondary mb-0 fw-semibold">Masuk ke panel staf</p>
                </div>

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            class="form-control @error('email') is-invalid @enderror"
                            autocomplete="username"
                            required
                            autofocus
                        >
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            class="form-control @error('password') is-invalid @enderror"
                            autocomplete="current-password"
                            required
                        >
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Masuk</button>
                </form>
            </div>
        </div>
    </main>
@endsection