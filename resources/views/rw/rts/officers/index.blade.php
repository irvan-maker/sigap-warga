@extends('layouts.app')

@section('title', 'Petugas RT - SIGAP WARGA')

@section('content')
<main class="container py-4" style="max-width: 960px">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('rw.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('rw.rts.index') }}">Kelola RT</a></li>
            <li class="breadcrumb-item"><a href="{{ route('rw.rts.edit', $managedRt) }}">{{ $managedRt->code }}</a></li>
            <li class="breadcrumb-item active">Petugas RT</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="text-uppercase small fw-semibold text-secondary mb-1">Manajemen Akses Wilayah</p>
            <h1 class="h2 mb-1">Petugas {{ $managedRt->code }}</h1>
            <p class="text-secondary mb-0">RW hanya dapat membuat dan mengelola akun petugas untuk RT di wilayahnya sendiri.</p>
        </div>
        <span class="badge text-bg-{{ $managedRt->is_active ? 'success' : 'secondary' }} px-3 py-2">
            RT {{ $managedRt->is_active ? 'Aktif' : 'Nonaktif' }}
        </span>
    </div>

    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @error('status')<div class="alert alert-danger">{{ $message }}</div>@enderror

    <section class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-1">Tambah Petugas RT</h2>
            <p class="text-secondary small mb-4">Role, RW, dan RT ditetapkan otomatis oleh sistem. Password minimal 12 karakter.</p>

            @if (! $managedRt->is_active)
                <div class="alert alert-warning mb-0">Aktifkan RT terlebih dahulu sebelum menambahkan petugas baru.</div>
            @else
                <form method="POST" action="{{ route('rw.rts.officers.store', $managedRt) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="name">Nama</label><input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label" for="email">Email</label><input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label" for="password">Password sementara</label><input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" autocomplete="new-password" required>@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label" for="password_confirmation">Konfirmasi password</label><input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required></div>
                    </div>
                    <button class="btn btn-primary mt-4" type="submit">Tambah Petugas RT</button>
                </form>
            @endif
        </div>
    </section>

    <section class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="mb-3">
                <h2 class="h5 fw-bold mb-1">Daftar Petugas</h2>
                <p class="text-secondary small mb-0">{{ $officers->count() }} akun terdaftar pada {{ $managedRt->code }}.</p>
            </div>

            @forelse ($officers as $officer)
                <article class="border rounded-3 p-3 p-lg-4 mb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <div><strong class="d-block">{{ $officer->name }}</strong><span class="text-secondary small">{{ $officer->email }}</span></div>
                        <span class="badge text-bg-{{ $officer->is_active ? 'success' : 'secondary' }}">{{ $officer->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </div>

                    <form method="POST" action="{{ route('rw.rts.officers.update', [$managedRt, $officer]) }}" class="row g-3 mb-3">
                        @csrf @method('PUT')
                        <div class="col-md-5"><label class="form-label">Nama</label><input class="form-control" name="name" value="{{ $officer->name }}" required></div>
                        <div class="col-md-5"><label class="form-label">Email</label><input class="form-control" name="email" type="email" value="{{ $officer->email }}" required></div>
                        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-primary w-100" type="submit">Simpan</button></div>
                    </form>

                    <div class="d-flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('rw.rts.officers.status.toggle', [$managedRt, $officer]) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-{{ $officer->is_active ? 'outline-danger' : 'success' }} btn-sm" type="submit">{{ $officer->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                        </form>

                        <details>
                            <summary class="btn btn-outline-secondary btn-sm">Reset Password</summary>
                            <form method="POST" action="{{ route('rw.rts.officers.password.reset', [$managedRt, $officer]) }}" class="border rounded-3 p-3 mt-2" style="min-width:300px">
                                @csrf @method('PATCH')
                                <div class="mb-2"><label class="form-label small">Password baru</label><input class="form-control form-control-sm" name="password" type="password" autocomplete="new-password" required></div>
                                <div class="mb-2"><label class="form-label small">Konfirmasi password</label><input class="form-control form-control-sm" name="password_confirmation" type="password" autocomplete="new-password" required></div>
                                <button class="btn btn-outline-primary btn-sm" type="submit">Reset Password</button>
                            </form>
                        </details>
                    </div>
                </article>
            @empty
                <div class="text-center text-secondary py-4">Belum ada akun petugas untuk {{ $managedRt->code }}.</div>
            @endforelse
        </div>
    </section>
</main>
@endsection
