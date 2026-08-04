@extends('layouts.app')

@section('title', 'Dashboard - SIGAP WARGA')

@section('content')
    <nav class="navbar navbar-expand-lg bg-white border-bottom">
        <div class="container">
            <span class="navbar-brand fw-semibold">SIGAP WARGA</span>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm">Keluar</button>
            </form>
        </div>
    </nav>

    <main class="container py-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3">Dashboard</h1>
                <p class="mb-1">Selamat datang, {{ auth()->user()->name }}.</p>
                <p class="text-secondary">Role: {{ auth()->user()->role->value }}</p>

                @if (auth()->user()->role === \App\Enums\UserRole::RT)
                    <a href="{{ route('rt.dashboard') }}" class="btn btn-primary">Buka Dashboard RT</a>
                @elseif (auth()->user()->role === \App\Enums\UserRole::RW)
                    <a href="{{ route('rw.dashboard') }}" class="btn btn-primary">Buka Dashboard RW</a>
                @elseif (auth()->user()->role === \App\Enums\UserRole::KELURAHAN)
                    <a href="{{ route('kelurahan.dashboard') }}" class="btn btn-primary">Buka Dashboard Kelurahan</a>
                @endif
            </div>
        </div>
    </main>
@endsection
