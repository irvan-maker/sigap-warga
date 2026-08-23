<section class="card border-0 shadow-sm mb-4" aria-labelledby="workflow-heading">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h2 id="workflow-heading" class="h4 mb-1">Penanganan Wilayah</h2>
                <p class="text-secondary mb-0">Tanggung jawab aktif dan riwayat disposisi laporan.</p>
            </div>
            <span class="badge text-bg-info">{{ $report->current_handling_level->label() }}</span>
        </div>
        <dl class="row mb-4">
            <dt class="col-sm-4">Kategori</dt><dd class="col-sm-8">{{ $report->category->label() }}</dd>
            <dt class="col-sm-4">Prioritas</dt><dd class="col-sm-8"><span class="badge text-bg-{{ $report->priority->bootstrapColor() }}">{{ $report->priority->label() }}</span></dd>
            <dt class="col-sm-4">Batas respons</dt><dd class="col-sm-8">@if($report->response_due_at){{ $report->response_due_at->format('d-m-Y H:i') }} @if(!$report->acknowledged_at && $report->response_due_at->isPast())<span class="badge text-bg-danger">Terlambat</span>@endif @else Belum ditetapkan @endif</dd>
            <dt class="col-sm-4">Target penyelesaian</dt><dd class="col-sm-8">@if($report->resolution_due_at){{ $report->resolution_due_at->format('d-m-Y H:i') }} @if(!in_array($report->status, [\App\Enums\ReportStatus::COMPLETED, \App\Enums\ReportStatus::REJECTED], true) && $report->resolution_due_at->isPast())<span class="badge text-bg-danger">Terlambat</span>@endif @else Belum ditetapkan @endif</dd>
            <dt class="col-sm-4">Titik masuk QR</dt><dd class="col-sm-8">{{ $report->entryRt?->code ?? 'Tidak melalui QR' }}</dd>
            <dt class="col-sm-4">Lokasi/RT kejadian</dt><dd class="col-sm-8">{{ $report->incidentRt?->code ?? $report->rt->code }}</dd>
            <dt class="col-sm-4">Penanggung jawab</dt>
            <dd class="col-sm-8 mb-0">
                @if($report->current_handling_level === \App\Enums\ReportHandlingLevel::RT)
                    {{ $report->currentRt?->name ?? 'RT belum ditentukan' }}
                @elseif($report->current_handling_level === \App\Enums\ReportHandlingLevel::RW)
                    {{ $report->currentRw?->name ?? 'RW belum ditentukan' }}
                @else
                    Kelurahan
                @endif
            </dd>
        </dl>

        <h3 class="h6">Riwayat disposisi</h3>
        @forelse($report->dispositions->sortBy('created_at') as $disposition)
            <div class="border rounded-3 p-3 {{ $loop->last ? '' : 'mb-2' }}">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <strong>{{ $disposition->from_level->label() }} → {{ $disposition->to_level->label() }}</strong>
                    <span class="badge text-bg-light">{{ $disposition->status->label() }}</span>
                </div>
                <p class="mb-1 mt-2">{{ $disposition->reason }}</p>
                <small class="text-secondary">
                    Oleh {{ $disposition->forwardedBy?->name ?? 'Sistem' }} · {{ $disposition->created_at->format('d-m-Y H:i') }}
                    @if($disposition->acknowledged_at)
                        · Diterima {{ $disposition->acknowledgedBy?->name ?? 'petugas' }} pada {{ $disposition->acknowledged_at->format('d-m-Y H:i') }}
                    @endif
                </small>
            </div>
        @empty
            <p class="text-secondary mb-0">Laporan belum pernah diteruskan.</p>
        @endforelse
    </div>
</section>
