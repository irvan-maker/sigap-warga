@extends('layouts.app')
@section('title', 'Lacak Surat - '.config('village.name'))
@section('content')
<main id="main-content" class="container py-5" style="max-width: 860px">
    <a href="{{ route('public.home') }}" class="d-inline-block mb-4"><i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke beranda</a>
    <div class="text-center mb-4"><h1 class="h2">Lacak Surat</h1><p class="text-secondary">Masukkan nomor pengajuan atau nomor surat beserta nomor HP warga.</p></div>
    <div class="card mb-4"><div class="card-body p-4"><form method="POST" action="{{ route('letter-tracking.store') }}">@csrf<div class="row g-3 align-items-end">
        <div class="col-md-5"><label for="reference" class="form-label">Nomor pengajuan / surat</label><input id="reference" name="reference" value="{{ old('reference') }}" class="form-control @error('reference') is-invalid @enderror" required aria-describedby="reference-help"><div id="reference-help" class="form-text">Contoh: SRT-XXXXXXXXXXXX atau nomor surat resmi.</div>@error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-5"><label for="phone" class="form-label">Nomor HP warga</label><input id="phone" name="phone" type="tel" autocomplete="tel" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" required aria-describedby="letter-phone-help"><div id="letter-phone-help" class="form-text">Nomor yang tercatat pada data pengajuan.</div>@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror @error('phone_normalized')<div class="text-danger small mt-1">Nomor HP tidak valid.</div>@enderror</div>
        <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit"><i class="bi bi-search me-1" aria-hidden="true"></i>Lacak</button></div>
    </div></form></div></div>
    @if($searched && !$letter)<div class="alert alert-warning" role="alert"><strong>Data belum dapat ditemukan.</strong> Periksa kembali kedua isian. Demi keamanan, kami tidak dapat memberi tahu bagian mana yang tidak cocok.</div>@endif
    @if($letter)<section class="card" aria-labelledby="letter-result"><div class="card-body p-4"><div class="d-flex justify-content-between flex-wrap gap-3 mb-4"><div><div class="text-secondary small">Nomor pengajuan</div><h2 id="letter-result" class="h4">{{ $letter->public_tracking_code }}</h2></div><span class="badge text-bg-{{ $letter->status->bootstrapColor() }} align-self-start">{{ $letter->status->label() }}</span></div>
        <dl class="row"><dt class="col-sm-5">Jenis surat</dt><dd class="col-sm-7">{{ $letter->letter_type->label() }}</dd><dt class="col-sm-5">Nomor surat</dt><dd class="col-sm-7">{{ $letter->letter_number ?: 'Belum diterbitkan' }}</dd><dt class="col-sm-5">Tanggal pengajuan</dt><dd class="col-sm-7">{{ $letter->created_at->format('d-m-Y H:i') }}</dd><dt class="col-sm-5">Tanggal penerbitan</dt><dd class="col-sm-7">{{ $letter->issued_at?->format('d-m-Y H:i') ?: 'Belum diterbitkan' }}</dd></dl>
        <h3 class="h5 mt-4">Timeline</h3><ol class="list-group list-group-numbered mb-4">@foreach($letter->histories as $history)<li class="list-group-item d-flex justify-content-between gap-3"><strong>{{ $history->new_status->label() }}</strong><time class="text-secondary small">{{ $history->created_at->format('d-m-Y H:i') }}</time></li>@endforeach</ol>
        @if($downloadUrl)<a class="btn btn-success" href="{{ $downloadUrl }}"><i class="bi bi-download me-2" aria-hidden="true"></i>Download PDF</a><p class="form-text mt-2 mb-0">Tautan aman ini berlaku selama 15 menit.</p>@endif
    </div></section>@endif
</main>
@endsection
