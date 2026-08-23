@extends('layouts.app')

@section('title', 'Master Jenis Surat - SIGAP WARGA')

@section('content')
    <main class="container py-4 py-lg-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="{{ route('kelurahan.dashboard') }}">Dashboard Desa</a></li>
                <li class="breadcrumb-item active" aria-current="page">Master Jenis Surat</li>
            </ol>
        </nav>

        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
            <div>
                <p class="admin-eyebrow mb-1">Konfigurasi Persuratan</p>
                <h1 class="h2 fw-bold mb-1">Master Jenis Surat</h1>
                <p class="text-secondary mb-0">Kelola identitas dan versioned configuration tanpa mengubah runtime Persuratan legacy.</p>
            </div>
            @can('create', \App\Models\LetterTypeDefinition::class)
                <a class="btn btn-primary" href="{{ route('kelurahan.letter-types.create') }}">Tambah Jenis Surat</a>
            @endcan
        </div>

        <section class="card border-0 shadow-sm mb-4" aria-labelledby="filter-heading">
            <div class="card-body p-4">
                <h2 id="filter-heading" class="h5 fw-bold mb-3">Cari Master</h2>
                <form class="row g-3 align-items-end" method="GET" action="{{ route('kelurahan.letter-types.index') }}">
                    <div class="col-lg-7">
                        <label class="form-label" for="search">Kode atau nama</label>
                        <input class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Contoh: DOMICILE_CERTIFICATE">
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Semua</option>
                            <option value="active" @selected(request('status') === 'active')>Aktif</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-3 d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1" type="submit">Cari</button>
                        <a class="btn btn-outline-secondary" href="{{ route('kelurahan.letter-types.index') }}">Reset</a>
                    </div>
                </form>
            </div>
        </section>

        <section class="card border-0 shadow-sm" aria-labelledby="types-heading">
            <div class="card-header bg-white border-0 p-4 pb-2 d-flex justify-content-between align-items-center gap-3">
                <h2 id="types-heading" class="h5 fw-bold mb-0">Daftar Jenis Surat</h2>
                <span class="badge rounded-pill text-bg-primary px-3 py-2">{{ number_format($letterTypes->total()) }} jenis</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th class="ps-4">Jenis Surat</th><th>Status</th><th>Published</th><th>Draft</th><th class="pe-4 text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($letterTypes as $letterType)
                            <tr>
                                <td class="ps-4"><strong class="d-block">{{ $letterType->name }}</strong><code>{{ $letterType->code }}</code></td>
                                <td><span class="badge text-bg-{{ $letterType->is_active ? 'success' : 'secondary' }}">{{ $letterType->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td>{{ $letterType->latestPublishedVersion ? 'v'.$letterType->latestPublishedVersion->version : '—' }}</td>
                                <td>{{ $letterType->draftVersion ? 'v'.$letterType->draftVersion->version : '—' }}</td>
                                <td class="pe-4 text-end">
                                    <div class="d-inline-flex gap-2">
                                        @if ($letterType->draftVersion)
                                            <a class="btn btn-primary btn-sm" href="{{ route('kelurahan.letter-type-versions.show', $letterType->draftVersion) }}">{{ auth()->user()->can('updateConfiguration', $letterType->draftVersion) ? 'Configure' : 'Review' }}</a>
                                        @endif
                                        <a class="btn btn-outline-primary btn-sm" href="{{ route('kelurahan.letter-types.edit', $letterType) }}">Detail</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-secondary px-4 py-5">Jenis surat tidak ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($letterTypes->hasPages())
                <div class="border-top px-4 py-3">{{ $letterTypes->links('pagination::bootstrap-5') }}</div>
            @endif
        </section>
    </main>
@endsection
