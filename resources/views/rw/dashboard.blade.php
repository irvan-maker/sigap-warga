@extends('layouts.app')

@section('title', 'Dashboard RW - SIGAP WARGA')

@section('content')
@php
    $user = auth()->user();
    $navigation = [
        ['label' => 'Dashboard', 'url' => route('rw.dashboard'), 'icon' => 'bi-grid-1x2', 'active' => true],
        ['label' => 'Laporan', 'url' => '#daftar-laporan', 'icon' => 'bi-inbox'],
        ['label' => 'Wilayah RT', 'url' => route('rw.rts.index'), 'icon' => 'bi-diagram-3'],
        ['label' => 'Warga', 'url' => route('rw.citizens.index'), 'icon' => 'bi-people'],
        ['label' => 'Kartu Keluarga', 'url' => route('rw.family-cards.index'), 'icon' => 'bi-card-heading'],
        ['label' => 'Surat', 'url' => route('rw.letters.index'), 'icon' => 'bi-envelope-check'],
    ];
@endphp

<div class="dashboard-workspace">
    <x-dashboard.topbar :home-url="route('rw.dashboard')" role-label="Dashboard RW" :context="$user->rw?->code ?? 'Wilayah RW'" :links="$navigation" />

    <main id="main-content" class="container dashboard-main">
        <x-dashboard.hero badge="Dashboard RW" title="Koordinasi wilayah dalam satu layar" description="Pantau laporan dari seluruh RT, tindak lanjut eskalasi, dan layanan administrasi warga." icon="bi-diagram-3">
            <x-slot:meta><small class="d-block mb-1">Wilayah tugas</small><strong class="d-block h5 mb-1">{{ $user->rw?->code ?? 'RW belum tersedia' }}</strong><span class="small text-white-50">{{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}</span></x-slot:meta>
        </x-dashboard.hero>

        <section class="module-strip" aria-label="Status modul sistem">
            <div class="module-strip-copy"><span class="module-strip-icon"><i class="bi bi-boxes" aria-hidden="true"></i></span><div><strong class="d-block">Fokus layanan RW</strong><small class="text-secondary">Koordinasi laporan cepat dan pemantauan lintas RT.</small></div></div>
            <div class="module-pills"><span class="module-pill module-pill-active">Laporan Cepat · PILOT</span><span class="module-pill module-pill-prototype">Sensus · PROTOTYPE</span><span class="module-pill module-pill-prototype">Posyandu · AGREGAT</span><span class="module-pill module-pill-prototype">Persuratan · PROTOTYPE</span></div>
        </section>

        <section class="dashboard-section" aria-labelledby="rw-kpi">
            <x-dashboard.section-heading eyebrow="Kinerja wilayah" title="Indikator Utama" description="Angka penting untuk menentukan pekerjaan hari ini." heading-id="rw-kpi" />
            <div class="row g-3">
                <div class="col-sm-6 col-xl-3"><x-dashboard.metric label="Total Laporan" :value="number_format($total)" helper="Seluruh RT dalam RW" icon="bi-inbox" tone="primary" href="#daftar-laporan" /></div>
                <div class="col-sm-6 col-xl-3"><x-dashboard.metric label="Laporan Baru" :value="number_format($totalsByStatus[\App\Enums\ReportStatus::NEW->value])" helper="Perlu dipantau" icon="bi-bell" tone="warning" href="#daftar-laporan" /></div>
                <div class="col-sm-6 col-xl-3"><x-dashboard.metric label="RT Aktif" :value="number_format($activeRtCount)" helper="Di bawah wilayah RW" icon="bi-geo-alt" tone="success" :href="route('rw.rts.index')" /></div>
                <div class="col-sm-6 col-xl-3"><x-dashboard.metric label="Warga Aktif" :value="number_format($activeCitizenCount)" helper="Data lintas RT" icon="bi-people" tone="info" :href="route('rw.citizens.index')" /></div>
            </div>
        </section>

        <section class="dashboard-section" aria-labelledby="rw-actions">
            <x-dashboard.section-heading eyebrow="Navigasi layanan" title="Aksi Cepat" description="Akses tugas wilayah tanpa mencari menu berulang kali." heading-id="rw-actions" />
            <div class="row g-3">
                <div class="col-sm-6 col-xl-3"><x-dashboard.action-card :href="route('rw.rts.index')" title="Kelola RT" description="Struktur dan status wilayah" icon="bi-diagram-3" /></div>
                <div class="col-sm-6 col-xl-3"><x-dashboard.action-card :href="route('rw.letters.index')" title="Verifikasi Surat" description="{{ number_format($letterCount) }} pengajuan tercatat" icon="bi-envelope-check" tone="warning" /></div>
                <div class="col-sm-6 col-xl-3"><x-dashboard.action-card :href="route('rw.citizens.index')" title="Monitoring Warga" description="{{ number_format($activeCitizenCount) }} warga aktif" icon="bi-people" tone="success" /></div>
                <div class="col-sm-6 col-xl-3"><x-dashboard.action-card :href="route('rw.family-cards.index')" title="Monitoring KK" description="{{ number_format($activeFamilyCardCount) }} kartu aktif" icon="bi-card-heading" tone="info" /></div>
            </div>
        </section>

        @if ($letterCount > 0)
            <section class="priority-panel dashboard-section d-flex flex-wrap justify-content-between align-items-center gap-3" aria-labelledby="rw-attention">
                <div><p class="section-eyebrow mb-1">Tindak lanjut</p><h2 id="rw-attention" class="h5 fw-bold mb-1">Perlu Perhatian</h2><p class="text-secondary small mb-0">{{ number_format($letterCount) }} pengajuan surat perlu ditinjau oleh petugas RW.</p></div>
                <a class="btn btn-warning" href="{{ route('rw.letters.index') }}"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>Tinjau Surat</a>
            </section>
        @endif

        <section id="daftar-laporan" class="card dashboard-panel-modern dashboard-section" aria-labelledby="rw-latest">
            <div class="card-header p-4">
                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-end gap-4">
                    <div><p class="section-eyebrow mb-1">Aktivitas terbaru</p><h2 id="rw-latest" class="h4 section-title mb-1">Laporan Terbaru</h2><p class="small text-secondary mb-0">Hanya laporan dari RT di wilayah RW Anda.</p></div>
                    <form method="GET" action="{{ route('rw.dashboard') }}" class="row g-2">
                        <div class="col-sm-auto"><label class="visually-hidden" for="rw-rt-filter">Filter RT</label><select id="rw-rt-filter" name="rt_id" class="form-select"><option value="">Semua RT</option>@foreach($rts as $rt)<option value="{{ $rt->id }}" @selected((int) request('rt_id') === $rt->id)>{{ $rt->code }}</option>@endforeach</select></div>
                        <div class="col-sm-auto"><label class="visually-hidden" for="rw-status-filter">Filter status</label><select id="rw-status-filter" name="status" class="form-select"><option value="">Semua status</option>@foreach(\App\Enums\ReportStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
                        <div class="col-sm-auto"><label class="visually-hidden" for="rw-search">Cari laporan</label><input id="rw-search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Tiket, warga, atau judul"></div>
                        <div class="col-sm-auto"><button class="btn btn-primary w-100" type="submit"><i class="bi bi-search me-1" aria-hidden="true"></i>Cari</button></div>
                    </form>
                </div>
            </div>
            <div class="table-responsive"><table class="table table-hover table-sticky align-middle mb-0"><thead><tr><th>Tiket</th><th>RT</th><th>Warga</th><th>Judul</th><th>Status</th><th><span class="visually-hidden">Aksi</span></th></tr></thead><tbody>
                @forelse($reports as $report)
                    @php($url = route('rw.reports.show', $report))
                    <tr data-row-url="{{ $url }}"><td><a class="fw-semibold text-decoration-none" href="{{ $url }}">{{ $report->ticket_number }}</a></td><td>{{ $report->rt->code }}</td><td>{{ $report->citizen?->name ?? 'Pelapor umum' }}</td><td>{{ $report->title }}</td><td><span class="badge badge-status text-bg-{{ $report->status->bootstrapColor() }}">{{ $report->status->label() }}</span></td><td><a class="btn btn-outline-primary btn-sm" href="{{ $url }}" aria-label="Detail {{ $report->ticket_number }}"><i class="bi bi-chevron-right" aria-hidden="true"></i></a></td></tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state"><span class="empty-state-icon"><i class="bi bi-clipboard-check" aria-hidden="true"></i></span><h3 class="h5">Belum ada laporan</h3><p class="text-secondary mb-3">Aktivitas laporan terbaru akan tampil di sini.</p><a class="btn btn-outline-primary" href="{{ route('rw.dashboard') }}">Reset Filter</a></div></td></tr>
                @endforelse
            </tbody></table></div>
            @if($reports->hasPages())<div class="card-footer bg-white p-3">{{ $reports->links('pagination::bootstrap-5') }}</div>@endif
        </section>

        <section class="card dashboard-panel-modern dashboard-section" aria-labelledby="rw-stats">
            <div class="card-body p-4"><p class="section-eyebrow mb-1">Statistik</p><h2 id="rw-stats" class="h4 section-title mb-3">Total Laporan per RT</h2><div class="row g-3">
                @forelse($rts as $rt)<div class="col-sm-6 col-lg-4"><div class="citizen-stat"><span class="text-secondary small">{{ $rt->code }} · {{ $rt->name }}</span><strong>{{ number_format($totalsByRt[$rt->id]) }}</strong><small class="text-secondary">laporan tercatat</small></div></div>
                @empty<div class="col-12"><div class="empty-state"><span class="empty-state-icon"><i class="bi bi-diagram-3" aria-hidden="true"></i></span><h3 class="h5">Belum ada data RT</h3><p class="text-secondary">Tambahkan RT untuk melihat statistik wilayah.</p><a class="btn btn-primary" href="{{ route('rw.rts.create') }}">Tambah RT</a></div></div>@endforelse
            </div></div>
        </section>

        @include('analytics.rw')
    </main>
</div>
@endsection
