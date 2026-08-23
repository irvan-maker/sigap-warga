@extends('layouts.app')

@section('title', 'Prototype Posyandu - SIGAP WARGA')

@section('content')
<main id="main-content" class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div><span class="badge text-bg-warning mb-2">PROTOTYPE · DATA TERBATAS</span><h1 class="h2 mb-1">Posyandu</h1><p class="text-secondary mb-0">Pencatatan dasar untuk petugas yang ditugaskan. Bukan sistem diagnosis atau rekam medis nasional.</p></div>
        <a class="btn btn-outline-secondary" href="{{ route('dashboard') }}">Kembali ke Dashboard</a>
    </div>

    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    <section class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
        <h2 class="h5">Penugasan Anda</h2>
        <div class="d-flex flex-wrap gap-2">@foreach($assignments as $assignment)<span class="badge text-bg-light">{{ $assignment->site->name }} · {{ $assignment->role->label() }}</span>@endforeach</div>
    </div></section>

    <div class="row g-4 mb-4">
        <div class="col-xl-6"><section class="card h-100 border-0 shadow-sm"><div class="card-body p-4">
            <h2 class="h4">Catat Kunjungan</h2>
            <form method="POST" action="{{ route('posyandu.visits.store') }}">@csrf
                <div class="mb-3"><label class="form-label" for="visit_site">Lokasi Posyandu</label><select id="visit_site" name="posyandu_site_id" class="form-select" required>@foreach($assignments as $assignment)<option value="{{ $assignment->site->id }}">{{ $assignment->site->name }}</option>@endforeach</select></div>
                <div class="mb-3"><label class="form-label" for="citizen_id">Warga</label><select id="citizen_id" name="citizen_id" class="form-select" required><option value="">Pilih warga</option>@foreach($citizens as $citizen)<option value="{{ $citizen->id }}">{{ $citizen->name }}</option>@endforeach</select></div>
                <div class="row g-3 mb-3"><div class="col-md-6"><label class="form-label" for="visited_at">Waktu kunjungan</label><input id="visited_at" name="visited_at" type="datetime-local" class="form-control" value="{{ old('visited_at', now()->format('Y-m-d\TH:i')) }}" required></div><div class="col-md-6"><label class="form-label" for="life_cycle_group">Kelompok siklus hidup</label><select id="life_cycle_group" name="life_cycle_group" class="form-select" required>@foreach($lifeCycleGroups as $group)<option value="{{ $group->value }}">{{ $group->label() }}</option>@endforeach</select></div></div>
                <div class="row g-3 mb-3"><div class="col-md-6"><label class="form-label" for="weight_kg">Berat (kg)</label><input id="weight_kg" name="weight_kg" type="number" step="0.01" min="0.1" max="500" class="form-control"></div><div class="col-md-6"><label class="form-label" for="height_cm">Tinggi (cm)</label><input id="height_cm" name="height_cm" type="number" step="0.01" min="20" max="250" class="form-control"></div></div>
                <div class="mb-3"><label class="form-label" for="notes">Catatan pelayanan</label><textarea id="notes" name="notes" rows="2" class="form-control"></textarea></div>
                <div class="mb-3"><label class="form-label" for="follow_up">Tindak lanjut</label><textarea id="follow_up" name="follow_up" rows="2" class="form-control"></textarea></div>
                <div class="form-check mb-3"><input id="referral_required" name="referral_required" value="1" type="checkbox" class="form-check-input"><label for="referral_required" class="form-check-label">Perlu rujukan/tindak lanjut petugas kesehatan</label></div>
                @if($errors->any())<div class="alert alert-danger">Periksa kembali data kunjungan yang dimasukkan.</div>@endif
                <button class="btn btn-primary" type="submit">Simpan Kunjungan</button>
            </form>
        </div></section></div>

        <div class="col-xl-6"><section class="card h-100 border-0 shadow-sm"><div class="card-body p-4">
            <h2 class="h4">Jadwal Kegiatan</h2>
            @if($canManageSchedules)
                <form method="POST" action="{{ route('posyandu.schedules.store') }}" class="border rounded-3 p-3 mb-4">@csrf
                    <div class="mb-2"><select name="posyandu_site_id" class="form-select" required>@foreach($assignments as $assignment)@if(in_array($assignment->role, [\App\Enums\PosyanduStaffRole::COORDINATOR, \App\Enums\PosyanduStaffRole::HEALTH_OFFICER], true))<option value="{{ $assignment->site->id }}">{{ $assignment->site->name }}</option>@endif @endforeach</select></div>
                    <div class="row g-2"><div class="col-md-6"><input name="service_date" type="date" min="{{ today()->format('Y-m-d') }}" class="form-control" required></div><div class="col-6 col-md-3"><input name="starts_at" type="time" class="form-control" aria-label="Jam mulai"></div><div class="col-6 col-md-3"><input name="ends_at" type="time" class="form-control" aria-label="Jam selesai"></div></div>
                    <button class="btn btn-outline-primary btn-sm mt-3" type="submit">Tambah Jadwal</button>
                </form>
            @endif
            @forelse($schedules as $schedule)<div class="border-bottom py-3"><strong>{{ $schedule->site->name }}</strong><div>{{ $schedule->service_date->locale('id')->isoFormat('dddd, D MMMM Y') }} {{ $schedule->starts_at ? '· '.$schedule->starts_at : '' }}</div></div>@empty<p class="text-secondary">Belum ada jadwal mendatang.</p>@endforelse
        </div></section></div>
    </div>

    <section class="card border-0 shadow-sm"><div class="card-body p-4"><h2 class="h4">Kunjungan Terbaru</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Waktu</th><th>Warga</th><th>Posyandu</th><th>Kelompok</th><th>Rujukan</th></tr></thead><tbody>@forelse($visits as $visit)<tr><td>{{ $visit->visited_at->format('d-m-Y H:i') }}</td><td>{{ $visit->citizen->name }}</td><td>{{ $visit->site->name }}</td><td>{{ $visit->life_cycle_group->label() }}</td><td>{{ $visit->referral_required ? 'Perlu' : 'Tidak' }}</td></tr>@empty<tr><td colspan="5" class="text-center text-secondary py-4">Belum ada kunjungan.</td></tr>@endforelse</tbody></table></div></div></section>
</main>
@endsection
