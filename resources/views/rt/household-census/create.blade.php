@extends('layouts.app')

@section('title', 'Sensus Keluarga - SIGAP WARGA')

@section('content')
<main class="container app-main" style="max-width: 960px">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('rt.dashboard') }}">Dashboard RT</a></li><li class="breadcrumb-item active">Sensus Keluarga</li></ol></nav>
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4"><div><p class="text-primary text-uppercase fw-semibold small mb-1">Administrasi Kependudukan</p><h1 class="h2 mb-1">Sensus Keluarga</h1><p class="text-secondary mb-0">Lengkapi data keluarga sesuai dokumen kependudukan yang berlaku.</p></div><a class="btn btn-outline-secondary" href="{{ route('rt.family-cards.index') }}"><i class="bi bi-card-list me-2" aria-hidden="true"></i>Daftar Kartu Keluarga</a></div>

    <nav class="card border-0 shadow-sm mb-4" aria-label="Tahapan sensus keluarga"><div class="card-body py-3"><div class="row g-2 text-center small fw-semibold"><div class="col-6 col-md-3 text-primary"><span class="badge rounded-pill text-bg-primary me-1">1</span>Kartu Keluarga</div><div class="col-6 col-md-3"><span class="badge rounded-pill text-bg-secondary me-1">2</span>Kepala</div><div class="col-6 col-md-3"><span class="badge rounded-pill text-bg-secondary me-1">3</span>Anggota</div><div class="col-6 col-md-3"><span class="badge rounded-pill text-bg-secondary me-1">4</span>Ringkasan</div></div></div></nav>

    @if($errors->any())<div class="alert alert-danger" role="alert">Periksa kembali data yang ditandai pada formulir.</div>@endif

    <form method="POST" action="{{ route('rt.household-census.store') }}" data-household-census>
        @csrf
        <section class="card border-0 shadow-sm mb-4" aria-labelledby="card-heading"><div class="card-body p-4 p-lg-5"><div class="d-flex align-items-center gap-3 border-bottom pb-3 mb-4"><span class="icon-box"><i class="bi bi-person-vcard" aria-hidden="true"></i></span><div><p class="text-primary text-uppercase fw-semibold small mb-1">Bagian 1</p><h2 id="card-heading" class="h5 mb-0">Data Kartu Keluarga</h2></div></div><div class="row g-3">
            <div class="col-md-6"><label class="form-label fw-semibold" for="family-number">Nomor KK <span class="text-danger">*</span></label><input id="family-number" inputmode="numeric" maxlength="16" class="form-control @error('family_number') is-invalid @enderror" name="family_number" value="{{ old('family_number') }}" required data-summary-family-number aria-describedby="family-number-help">@error('family_number')<div class="invalid-feedback">{{ $message }}</div>@enderror<div id="family-number-help" class="form-text">Nomor KK menjadi identitas seluruh anggota keluarga.</div></div>
            <div class="col-md-6"><label class="form-label fw-semibold" for="address">Alamat <span class="text-danger">*</span></label><textarea id="address" class="form-control @error('address') is-invalid @enderror" name="address" rows="3" required data-summary-address>{{ old('address') }}</textarea>@error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-text">Tuliskan alamat domisili keluarga secara lengkap sesuai wilayah RT.</div></div>
        </div></div></section>

        <section class="card border-0 shadow-sm mb-4" aria-labelledby="head-heading"><div class="card-body p-4 p-lg-5"><div class="d-flex flex-wrap justify-content-between align-items-center gap-3 border-bottom pb-3 mb-4"><div class="d-flex align-items-center gap-3"><span class="icon-box"><i class="bi bi-person-check" aria-hidden="true"></i></span><div><p class="text-primary text-uppercase fw-semibold small mb-1">Bagian 2</p><h2 id="head-heading" class="h5 mb-0">Data Kepala Keluarga</h2></div></div><span class="badge rounded-pill text-bg-primary px-3 py-2"><i class="bi bi-star-fill me-1" aria-hidden="true"></i>KEPALA KELUARGA</span></div><div class="row g-3">
            <div class="col-md-6"><label class="form-label" for="head-name">Nama <span class="text-danger">*</span></label><input id="head-name" class="form-control @error('head.name') is-invalid @enderror" name="head[name]" value="{{ old('head.name') }}" required data-summary-head-name>@error('head.name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            @include('rt.household-census.person-fields', ['prefix' => 'head', 'person' => old('head', []), 'idPrefix' => 'head'])
        </div><div class="alert alert-light border mt-4 mb-0"><i class="bi bi-info-circle text-primary me-2" aria-hidden="true"></i>Hubungan keluarga ditetapkan otomatis sebagai <strong>KEPALA KELUARGA</strong>. Wilayah mengikuti akun petugas.</div></div></section>

        <section class="card border-0 shadow-sm mb-4" aria-labelledby="members-heading"><div class="card-body p-4 p-lg-5"><div class="d-flex align-items-center gap-3 border-bottom pb-3 mb-4"><span class="icon-box"><i class="bi bi-people" aria-hidden="true"></i></span><div><p class="text-primary text-uppercase fw-semibold small mb-1">Bagian 3</p><h2 id="members-heading" class="h5 mb-0">Data Anggota Keluarga</h2></div></div>
            <div data-members>@foreach(old('members', []) as $index => $person) @include('rt.household-census.member', ['index' => $index, 'person' => $person]) @endforeach</div>
            <div class="text-center border rounded-3 p-4 mb-4 @if(old('members', []) !== []) d-none @endif" data-members-empty><i class="bi bi-person-plus fs-2 text-secondary" aria-hidden="true"></i><p class="fw-semibold mt-2 mb-1">Belum ada anggota keluarga</p><p class="text-secondary small mb-0">Tambahkan pasangan, anak, orang tua, atau anggota lain dalam Kartu Keluarga ini.</p></div>
            <template data-member-template>@include('rt.household-census.member', ['index' => '__INDEX__', 'person' => []])</template>
            <button class="btn btn-outline-primary btn-lg w-100" type="button" data-add-member aria-label="Tambah anggota keluarga"><i class="bi bi-plus-circle me-2" aria-hidden="true"></i>Tambah Anggota</button>
        </div></section>

        <section class="card border-0 shadow-sm mb-4" aria-labelledby="summary-heading"><div class="card-body p-4 p-lg-5"><div class="d-flex align-items-center gap-3 border-bottom pb-3 mb-4"><span class="icon-box"><i class="bi bi-clipboard-check" aria-hidden="true"></i></span><div><p class="text-primary text-uppercase fw-semibold small mb-1">Bagian 4</p><h2 id="summary-heading" class="h5 mb-0">Ringkasan</h2></div></div><div class="row g-3" aria-live="polite">
            @foreach([['Nomor KK','family-number'],['Nama Kepala Keluarga','head-name'],['Jumlah anggota','member-count'],['RT','rt'],['RW','rw'],['Alamat','address'],['Jumlah warga yang akan dibuat','citizen-count']] as [$label,$key])<div class="col-sm-6 {{ $key === 'address' ? 'col-lg-6' : 'col-lg-4' }}"><div class="border rounded-3 p-3 h-100"><span class="small text-secondary d-block">{{ $label }}</span><strong data-summary="{{ $key }}">@switch($key)@case('rt'){{ auth()->user()->rt?->code ?? '—' }}@break @case('rw'){{ auth()->user()->rw?->code ?? '—' }}@break @case('member-count'){{ count(old('members', [])) }}@break @case('citizen-count'){{ count(old('members', [])) + 1 }}@break @default—@endswitch</strong></div></div>@endforeach
        </div></div></section>

        <section class="card border-0 shadow-sm" aria-labelledby="save-heading"><div class="card-body p-4 p-lg-5 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3"><div><p class="text-primary text-uppercase fw-semibold small mb-1">Bagian 5</p><h2 id="save-heading" class="h5 mb-1">Simpan</h2><p class="text-secondary small mb-0"><i class="bi bi-shield-check me-1" aria-hidden="true"></i>Pastikan data sesuai dokumen sebelum disimpan.</p></div><button class="btn btn-primary btn-lg px-4" type="submit"><i class="bi bi-floppy me-2" aria-hidden="true"></i>Simpan Sensus Keluarga</button></div></section>
    </form>
</main>
@endsection
