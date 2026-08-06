@extends('layouts.app')
@section('title', 'Tambah Pengguna - SIGAP WARGA')
@section('content')
    <div class="admin-dashboard min-vh-100"><main class="container py-4 py-lg-5" style="max-width: 960px;">
        <nav aria-label="breadcrumb"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Pengguna</a></li><li class="breadcrumb-item active" aria-current="page">Tambah</li></ol></nav>
        <div class="mb-4"><p class="admin-eyebrow mb-1">Administrasi Akses</p><h1 class="h2 fw-bold mb-1">Tambah Pengguna</h1><p class="text-secondary mb-0">Buat akun dan tentukan penempatan sesuai role.</p></div>
        <section class="card admin-panel border-0 shadow-sm"><form method="POST" action="{{ route('admin.users.store') }}">@csrf<div class="card-body p-4">@include('admin.users._form')</div><div class="card-footer bg-white border-top p-4 d-flex gap-2"><button class="btn btn-primary" type="submit">Simpan Pengguna</button><a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">Batal</a></div></form></section>
    </main></div>
@endsection
