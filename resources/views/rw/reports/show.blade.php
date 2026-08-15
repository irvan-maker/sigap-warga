@extends('layouts.app')

@section('title', 'Detail Laporan RW - SIGAP WARGA')

@section('content')
    <main class="container py-5" style="max-width: 900px;">
        <a href="{{ route('rw.dashboard') }}" class="btn btn-outline-secondary mb-4">Kembali</a>

        @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
        @error('workflow')<div class="alert alert-danger">{{ $message }}</div>@enderror

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
            <form method="POST" action="{{ route('rw.reports.acknowledge', $report) }}" class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
                @csrf
                <h2 class="h4">Terima Disposisi</h2>
                <p class="text-secondary">Konfirmasi bahwa RW telah menerima tanggung jawab laporan ini.</p>
                <button class="btn btn-success" type="submit">Terima dan Mulai Proses</button>
            </div></form>
        @endif

        @if($canForward)
            <section class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
                <h2 class="h4">Teruskan Laporan</h2>
                <form method="POST" action="{{ route('rw.reports.forward', $report) }}">
                    @csrf
                    <div class="mb-3"><label for="target_level" class="form-label">Tujuan</label><select id="target_level" name="target_level" class="form-select" required><option value="KELURAHAN">Kelurahan</option><option value="RT">RT lain dalam RW ini</option></select></div>
                    <div class="mb-3"><label for="target_rt_id" class="form-label">RT tujuan (wajib jika memilih RT)</label><select id="target_rt_id" name="target_rt_id" class="form-select"><option value="">Pilih RT</option>@foreach($targetRts as $rt)<option value="{{ $rt->id }}">{{ $rt->code }} — {{ $rt->name }}</option>@endforeach</select></div>
                    <div class="mb-3"><label for="reason" class="form-label">Alasan disposisi</label><textarea id="reason" name="reason" rows="3" class="form-control @error('reason') is-invalid @enderror" required>{{ old('reason') }}</textarea>@error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <button class="btn btn-outline-primary" type="submit">Teruskan</button>
                </form>
            </div></section>
        @endif

        @include('reports.partials.status-form', ['statusRoute' => route('rw.reports.status.update', $report)])

        <div class="card border-0 shadow-sm"><div class="card-body p-4">
            <h2 class="h4">Riwayat Status</h2>
            <ol class="list-group list-group-numbered">
                @foreach ($histories as $history)
                    <li class="list-group-item">
                        <strong>{{ $history->new_status->value }}</strong>
                        <span class="text-secondary small ms-2">{{ $history->created_at->format('d-m-Y H:i') }}</span>
                        @if ($history->note)<p class="mb-0 mt-2">{{ $history->note }}</p>@endif
                    </li>
                @endforeach
            </ol>
        </div></div>
    </main>
@endsection
