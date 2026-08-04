@extends('layouts.app')

@section('title', 'Buat Laporan - SIGAP WARGA')

@section('content')
    <main class="container py-5" style="max-width: 760px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 mb-1">Buat Laporan</h1>
                <p class="text-secondary mb-0">Input manual untuk operasional internal.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Dashboard</a>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('reports.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="rt_id" class="form-label">RT</label>
                        <select id="rt_id" name="rt_id" class="form-select @error('rt_id') is-invalid @enderror" required>
                            <option value="">Pilih RT</option>
                            @foreach ($rts as $rt)
                                <option value="{{ $rt->id }}" @selected(old('rt_id') == $rt->id)>
                                    {{ $rt->code }} — {{ $rt->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('rt_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="citizen_name" class="form-label">Nama warga</label>
                            <input id="citizen_name" name="citizen_name" type="text" value="{{ old('citizen_name') }}" class="form-control @error('citizen_name') is-invalid @enderror" required>
                            @error('citizen_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Nomor telepon</label>
                            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" placeholder="0812 3456 7890" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @error('phone_normalized')
                                <div class="text-danger small mt-1">Nomor telepon tidak valid.</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label">Judul laporan</label>
                        <input id="title" name="title" type="text" value="{{ old('title') }}" class="form-control @error('title') is-invalid @enderror" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label">Deskripsi laporan</label>
                        <textarea id="description" name="description" rows="5" class="form-control @error('description') is-invalid @enderror" required>{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan laporan</button>
                </form>
            </div>
        </div>
    </main>
@endsection
