@extends('layouts.app')

@section('title', 'Detail Laporan RT - SIGAP WARGA')

@section('content')
    <main class="container py-5" style="max-width: 900px;">
        <a href="{{ route('rt.dashboard') }}" class="btn btn-outline-secondary mb-4">Kembali</a>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="d-flex justify-content-between gap-3 mb-4">
            <div><p class="text-secondary mb-1">Nomor tiket</p><h1 class="h2">{{ $report->ticket_number }}</h1></div>
            <span class="badge text-bg-primary align-self-start fs-6">{{ $report->status->value }}</span>
        </div>

        <div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
            <dl class="row mb-0">
                <dt class="col-sm-4">Warga</dt><dd class="col-sm-8">{{ $report->citizen?->name ?? 'Pelapor umum' }}</dd>
                <dt class="col-sm-4">RT</dt><dd class="col-sm-8">{{ $report->rt->code }} — {{ $report->rt->name }}</dd>
                <dt class="col-sm-4">Judul</dt><dd class="col-sm-8">{{ $report->title }}</dd>
                <dt class="col-sm-4">Deskripsi</dt><dd class="col-sm-8">{{ $report->description }}</dd>
                <dt class="col-sm-4">Tanggal laporan</dt><dd class="col-sm-8 mb-0">{{ $report->reported_at->format('d-m-Y H:i') }}</dd>
            </dl>
        </div></div>

        @include('reports.partials.attachments', ['attachments' => $report->attachments])

        @include('reports.partials.workflow-summary')

        @if($canAcknowledge)
            <section class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
                <h2 class="h4">Terima Disposisi</h2>
                <p class="text-secondary">Konfirmasi bahwa RT ini telah menerima tanggung jawab penanganan.</p>
                <form method="POST" action="{{ route('rt.reports.acknowledge', $report) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Terima dan Mulai Proses</button>
                </form>
            </div></section>
        @endif

        @if($canForward)
            <section class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
                <h2 class="h4">Teruskan kepada RW</h2>
                <p class="text-secondary">Gunakan jika penanganan berada di luar kewenangan RT.</p>
                <form method="POST" action="{{ route('rt.reports.forward', $report) }}">
                    @csrf
                    <input type="hidden" name="target_level" value="RW">
                    <div class="mb-3">
                        <label for="reason" class="form-label">Alasan disposisi internal</label>
                        <textarea id="reason" name="reason" rows="3" class="form-control @error('reason') is-invalid @enderror" required>{{ old('reason') }}</textarea>
                        <div class="form-text">Hanya dilihat petugas. Gunakan untuk konteks koordinasi internal.</div>
                        @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="forward_public_note" class="form-label">Pembaruan untuk warga</label>
                        <textarea id="forward_public_note" name="public_note" rows="3" class="form-control @error('public_note') is-invalid @enderror" required>{{ old('public_note', 'Laporan telah diverifikasi RT dan diteruskan kepada RW untuk koordinasi penanganan lanjutan.') }}</textarea>
                        <div class="form-text">Akan tampil pada halaman lacak laporan. Jangan masukkan informasi internal atau sensitif.</div>
                        @error('public_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button class="btn btn-outline-primary" type="submit">Teruskan ke RW</button>
                </form>
            </div></section>
        @endif

        @include('reports.partials.status-form', ['statusRoute' => route('rt.reports.status.update', $report)])

        @include('reports.partials.status-history')
    </main>
@endsection
