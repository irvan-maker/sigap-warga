@extends('layouts.app')
@section('title', $contextCard ? 'Tambah Anggota' : ($citizen->exists ? 'Edit Warga' : 'Tambah Warga'))
@section('content')
<main class="container py-4">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route($routePrefix.'.dashboard') }}">Dashboard</a></li>@if($contextCard)<li class="breadcrumb-item"><a href="{{ route($routePrefix.'.family-cards.index') }}">Kartu Keluarga</a></li><li class="breadcrumb-item"><a href="{{ route($routePrefix.'.family-cards.show', $contextCard) }}">{{ $contextCard->family_number }}</a></li><li class="breadcrumb-item active">Tambah Anggota</li>@else<li class="breadcrumb-item"><a href="{{ route($routePrefix.'.citizens.index') }}">Warga</a></li><li class="breadcrumb-item active">{{ $citizen->exists ? 'Edit' : 'Tambah' }}</li>@endif</ol></nav>
    <h1 class="h3 mb-3">{{ $contextCard ? 'Tambah Anggota' : ($citizen->exists ? 'Edit Warga' : 'Tambah Warga') }}</h1>
    @if($contextCard)<div class="alert alert-info"><strong>KK {{ $contextCard->family_number }}</strong><br>RW {{ $contextCard->rt->rw->code }} / RT {{ $contextCard->rt->code }}. Wilayah dan KK ditetapkan otomatis.</div>@endif
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ $contextCard ? route($routePrefix.'.family-cards.members.store', $contextCard) : ($citizen->exists ? route($routePrefix.'.citizens.update', $citizen) : route($routePrefix.'.citizens.store')) }}" class="card border-0 shadow-sm">
        <div class="card-body p-4">@csrf @if($citizen->exists)@method('PUT')@endif
            @if(!$contextCard && !$citizen->exists && auth()->user()->rt_id === null)<div class="mb-3"><label for="region_rt_id" class="form-label">Wilayah RT</label><select id="region_rt_id" name="region_rt_id" class="form-select" required><option value="">Pilih RT</option>@foreach($rts as $rt)<option value="{{ $rt->id }}" @selected(old('region_rt_id') == $rt->id)>RW {{ $rt->rw->code }} / RT {{ $rt->code }}</option>@endforeach</select></div>@endif
            <div class="row g-3">
                <div class="col-12"><h2 class="h6 text-uppercase text-secondary border-bottom pb-2 mb-0">Identitas</h2></div>
                <div class="col-md-7"><label for="name" class="form-label">Nama</label><input id="name" name="name" class="form-control" required value="{{ old('name', $citizen->name) }}"></div>
                <div class="col-md-5"><label for="nik" class="form-label">NIK</label><input id="nik" name="nik" class="form-control" inputmode="numeric" maxlength="16" value="{{ old('nik', $citizen->nik) }}"><div class="form-text">NIK harus terdiri dari 16 digit jika diisi.</div></div>
                @if(!$contextCard)<div class="col-md-6"><label for="family_card_id" class="form-label">Kartu Keluarga</label><select id="family_card_id" name="family_card_id" class="form-select"><option value="">Tanpa KK</option>@foreach($cards as $card)<option value="{{ $card->id }}" @selected(old('family_card_id', $citizen->family_card_id) == $card->id)>{{ $card->family_number }}</option>@endforeach</select></div>@endif

                <div class="col-12 mt-4"><h2 class="h6 text-uppercase text-secondary border-bottom pb-2 mb-0">Kontak</h2></div>
                <div class="col-md-6"><label for="phone" class="form-label">Telepon</label><input id="phone" name="phone" class="form-control" value="{{ old('phone', $citizen->phone) }}"><div class="form-text">Nomor telepon bersifat opsional.</div></div>

                <div class="col-12 mt-4"><h2 class="h6 text-uppercase text-secondary border-bottom pb-2 mb-0">Kelahiran</h2></div>
                <div class="col-md-6"><label for="birth_place" class="form-label">Tempat Lahir</label><input id="birth_place" name="birth_place" class="form-control" value="{{ old('birth_place', $citizen->birth_place) }}"></div>
                <div class="col-md-6"><label for="birth_date" class="form-label">Tanggal Lahir</label><input id="birth_date" type="date" name="birth_date" class="form-control" value="{{ old('birth_date', $citizen->birth_date?->format('Y-m-d')) }}"></div>

                <div class="col-12 mt-4"><h2 class="h6 text-uppercase text-secondary border-bottom pb-2 mb-0">Keluarga</h2></div>
                <div class="col-md-6"><label for="gender" class="form-label">Jenis Kelamin</label><select id="gender" name="gender" class="form-select"><option value="">Belum diisi</option><option value="L" @selected(old('gender', $citizen->gender) === 'L')>Laki-laki</option><option value="P" @selected(old('gender', $citizen->gender) === 'P')>Perempuan</option></select></div>
                <div class="col-md-6"><label for="family_relationship" class="form-label">Hubungan dalam Keluarga</label><select id="family_relationship" name="family_relationship" class="form-select"><option value="">Belum ditentukan</option>@foreach(\App\Enums\FamilyRelationship::cases() as $relationship)@continue($relationship === \App\Enums\FamilyRelationship::HEAD)<option value="{{ $relationship->value }}" @selected(old('family_relationship', $citizen->family_relationship?->value) === $relationship->value)>{{ $relationship->label() }}</option>@endforeach</select><div class="form-text">Hubungan Kepala Keluarga hanya satu per KK dan ditetapkan dari detail KK.</div></div>

                <div class="col-12 mt-4"><h2 class="h6 text-uppercase text-secondary border-bottom pb-2 mb-0">Domisili</h2></div>
                <div class="col-12"><label for="address" class="form-label">Alamat</label><textarea id="address" name="address" class="form-control" rows="4">{{ old('address', $citizen->address ?: $contextCard?->address) }}</textarea></div>
            </div>
            <div class="d-flex gap-2 mt-4"><button class="btn btn-primary">{{ $contextCard ? 'Simpan Anggota' : 'Simpan Warga' }}</button><a class="btn btn-outline-secondary" href="{{ $contextCard ? route($routePrefix.'.family-cards.show', $contextCard) : route($routePrefix.'.citizens.index') }}">Batal</a></div>
        </div>
    </form>
    @if($citizen->exists)<form method="POST" action="{{ route($routePrefix.'.citizens.status.toggle', $citizen) }}" class="mt-3">@csrf @method('PATCH')<button class="btn btn-outline-{{ $citizen->is_active ? 'danger' : 'success' }}">{{ $citizen->is_active ? 'Nonaktifkan' : 'Aktifkan' }} Warga</button></form>@endif
</main>
@endsection
