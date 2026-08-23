@extends('layouts.app')

@section('title', 'Tambah Jenis Surat - SIGAP WARGA')

@section('content')
    <main class="container py-4 py-lg-5">
        <nav aria-label="breadcrumb"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="{{ route('kelurahan.dashboard') }}">Dashboard Desa</a></li><li class="breadcrumb-item"><a href="{{ route('kelurahan.letter-types.index') }}">Master Jenis Surat</a></li><li class="breadcrumb-item active">Tambah</li></ol></nav>
        <div class="mb-4"><p class="admin-eyebrow mb-1">Master Persuratan</p><h1 class="h2 fw-bold mb-1">Tambah Jenis Surat</h1><p class="text-secondary mb-0">Draft version 1 akan dibuat otomatis untuk configuration awal.</p></div>
        <form class="card border-0 shadow-sm" method="POST" action="{{ route('kelurahan.letter-types.store') }}">
            @csrf
            <div class="card-body p-4">
                @include('kelurahan.letter-types._form', ['letterType' => null])
            </div>
            <div class="card-footer bg-white border-top p-4 d-flex justify-content-end gap-2"><a class="btn btn-outline-secondary" href="{{ route('kelurahan.letter-types.index') }}">Batal</a><button class="btn btn-primary" type="submit">Simpan dan Configure</button></div>
        </form>
    </main>
@endsection
