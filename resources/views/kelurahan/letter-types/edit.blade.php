@extends('layouts.app')

@section('title', 'Detail Jenis Surat - SIGAP WARGA')

@section('content')
    @php
        $canUpdate = auth()->user()->can('update', $letterType);
        $draft = $letterType->versions->first(fn ($version) => $version->isDraft());
    @endphp
    <main class="container py-4 py-lg-5">
        <nav aria-label="breadcrumb"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="{{ route('kelurahan.dashboard') }}">Dashboard Desa</a></li><li class="breadcrumb-item"><a href="{{ route('kelurahan.letter-types.index') }}">Master Jenis Surat</a></li><li class="breadcrumb-item active">{{ $letterType->code }}</li></ol></nav>
        @if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4"><div><p class="admin-eyebrow mb-1">Master Persuratan</p><h1 class="h2 fw-bold mb-1">{{ $letterType->name }}</h1><p class="text-secondary mb-0"><code>{{ $letterType->code }}</code></p></div>@if ($draft)<a class="btn btn-primary" href="{{ route('kelurahan.letter-type-versions.show', $draft) }}">{{ $canUpdate ? 'Configure' : 'Review' }} Draft v{{ $draft->version }}</a>@elseif ($canUpdate)<form method="POST" action="{{ route('kelurahan.letter-types.versions.store', $letterType) }}">@csrf<button class="btn btn-primary" type="submit">Buat Draft Baru</button></form>@endif</div>

        <form class="card border-0 shadow-sm mb-4" method="POST" action="{{ route('kelurahan.letter-types.update', $letterType) }}">
            @csrf
            @method('PUT')
            <div class="card-body p-4">@include('kelurahan.letter-types._form', ['canEdit' => $canUpdate])</div>
            @if ($canUpdate)<div class="card-footer bg-white border-top p-4 text-end"><button class="btn btn-primary" type="submit">Simpan Master</button></div>@endif
        </form>

        <section class="card border-0 shadow-sm" aria-labelledby="versions-heading">
            <div class="card-header bg-white border-0 p-4 pb-2"><h2 id="versions-heading" class="h5 fw-bold mb-0">Configuration Versions</h2></div>
            <div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th class="ps-4">Version</th><th>Status</th><th>Dibuat oleh</th><th>Published</th><th class="pe-4 text-end">Aksi</th></tr></thead><tbody>
                @forelse ($letterType->versions as $version)
                    <tr><td class="ps-4 fw-semibold">v{{ $version->version }}</td><td><span class="badge text-bg-{{ $version->isPublished() ? 'success' : 'warning' }}">{{ $version->status->value }}</span></td><td>{{ $version->creator?->name ?? 'Sistem' }}</td><td>{{ $version->published_at?->format('d M Y H:i') ?? '—' }}</td><td class="pe-4 text-end"><a class="btn btn-outline-primary btn-sm" href="{{ route('kelurahan.letter-type-versions.show', $version) }}">{{ $version->isDraft() && $canUpdate ? 'Configure' : 'Review' }}</a></td></tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-5">Belum ada configuration version.</td></tr>
                @endforelse
            </tbody></table></div>
        </section>
    </main>
@endsection
