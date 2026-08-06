@extends('layouts.app')
@section('title', $familyCard->exists ? 'Edit Kartu Keluarga' : 'Tambah Kartu Keluarga')
@section('content')
<main class="container py-4">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route($routePrefix.'.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route($routePrefix.'.family-cards.index') }}">Kartu Keluarga</a></li>@if($familyCard->exists)<li class="breadcrumb-item"><a href="{{ route($routePrefix.'.family-cards.show', $familyCard) }}">{{ $familyCard->family_number }}</a></li>@endif<li class="breadcrumb-item active">{{ $familyCard->exists ? 'Edit' : 'Tambah' }}</li></ol></nav>
    <h1 class="h3 mb-3">{{ $familyCard->exists ? 'Edit Kartu Keluarga' : 'Tambah Kartu Keluarga' }}</h1>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ $familyCard->exists ? route($routePrefix.'.family-cards.update', $familyCard) : route($routePrefix.'.family-cards.store') }}" class="card border-0 shadow-sm"><div class="card-body p-4">@csrf @if($familyCard->exists)@method('PUT')@endif
        @if(!$familyCard->exists && auth()->user()->rt_id === null)<div class="mb-3"><label for="region_rt_id" class="form-label">Wilayah RT</label><select id="region_rt_id" name="region_rt_id" class="form-select" required><option value="">Pilih RT</option>@foreach($rts as $rt)<option value="{{ $rt->id }}" @selected(old('region_rt_id') == $rt->id)>RW {{ $rt->rw->code }} / RT {{ $rt->code }}</option>@endforeach</select></div>@endif
        <div class="row g-3"><div class="col-md-6"><label for="family_number" class="form-label">Nomor KK</label><input id="family_number" name="family_number" class="form-control" inputmode="numeric" maxlength="16" required value="{{ old('family_number', $familyCard->family_number) }}"><div class="form-text">Nomor KK terdiri dari 16 digit dan harus unik.</div></div><div class="col-12"><label for="address" class="form-label">Alamat</label><textarea id="address" name="address" class="form-control" rows="4">{{ old('address', $familyCard->address) }}</textarea></div></div>
        <div class="alert alert-light border mt-3 mb-0">Kepala keluarga dipilih dari halaman detail KK setelah data anggota tersedia.</div>
        <div class="d-flex gap-2 mt-4"><button class="btn btn-primary">Simpan Kartu Keluarga</button><a class="btn btn-outline-secondary" href="{{ $familyCard->exists ? route($routePrefix.'.family-cards.show', $familyCard) : route($routePrefix.'.family-cards.index') }}">Batal</a></div>
    </div></form>
</main>
@endsection
