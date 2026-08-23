@extends('layouts.app')

@section('title', 'QR Layanan - SIGAP WARGA')

@section('content')
<main id="main-content" class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <span class="badge text-bg-success mb-2">PILOT</span>
            <h1 class="h2 mb-2">QR Layanan Warga</h1>
            <p class="text-secondary mb-0">Setiap RT hanya boleh memiliki satu QR aktif. QR membuka portal verifikasi sebelum warga melanjutkan ke WhatsApp SIGAP WARGA.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('dashboard') }}">Kembali</a>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @isset($issuedEntryPoint)
        <section class="card border-success shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="row g-4 align-items-center">
                    <div class="col-md-auto text-center">
                        <img src="{{ $qrDataUri }}" width="300" height="300" alt="QR layanan {{ $issuedEntryPoint->rt->code }}">
                    </div>
                    <div class="col">
                        <span class="badge text-bg-success mb-2">QR BARU</span>
                        <h2 class="h4">{{ $issuedEntryPoint->label ?: 'Pintu layanan' }} — {{ $issuedEntryPoint->rt->rw->code }} / {{ $issuedEntryPoint->rt->code }}</h2>
                        <p>Uji dengan kamera ponsel. QR membuka halaman wilayah, kemudian warga menekan tombol <strong>Mulai di WhatsApp</strong>.</p>
                        <div class="alert alert-warning"><strong>Simpan atau cetak sekarang.</strong> Tautan bertoken hanya ditampilkan satu kali dan token asli tidak disimpan di basis data.</div>
                        <a class="btn btn-primary" href="{{ $gatewayUrl }}" target="_blank" rel="noopener">Uji halaman QR</a>
                        <button class="btn btn-outline-secondary" type="button" onclick="window.print()">Cetak halaman</button>
                    </div>
                </div>
            </div>
        </section>
    @endisset

    <div class="row g-4">
        <div class="col-lg-5">
            <section class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h4">Terbitkan QR</h2>
                    <p class="text-secondary small">Untuk pilot, setiap RT hanya dapat memiliki satu QR aktif. Nonaktifkan QR lama sebelum menerbitkan pengganti.</p>
                    <form method="POST" action="{{ route('admin.service-entry-points.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="rt_id">Wilayah RT</label>
                            <select class="form-select" id="rt_id" name="rt_id" required>
                                <option value="">Pilih wilayah</option>
                                @foreach ($rts as $rt)
                                    <option value="{{ $rt->id }}" @selected((string) old('rt_id') === (string) $rt->id)>{{ $rt->rw->code }} / {{ $rt->code }} — {{ $rt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="label">Label lokasi</label>
                            <input class="form-control" id="label" name="label" maxlength="100" value="{{ old('label') }}" placeholder="Contoh: Balai Warga RT 001">
                        </div>
                        <button class="btn btn-primary" type="submit" @disabled($rts->isEmpty())>Buat QR Baru</button>
                    </form>
                </div>
            </section>
        </div>
        <div class="col-lg-7">
            <section class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h4">Daftar QR Terbit</h2>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead><tr><th>Wilayah</th><th>Label</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                            @forelse ($entryPoints as $entryPoint)
                                <tr>
                                    <td>{{ $entryPoint->rt->rw->code }} / {{ $entryPoint->rt->code }}</td>
                                    <td>{{ $entryPoint->label ?: '—' }}</td>
                                    <td>
                                        <span class="badge text-bg-{{ $entryPoint->isAvailable() ? 'success' : 'secondary' }}">{{ $entryPoint->isAvailable() ? 'Aktif' : 'Nonaktif' }}</span>
                                        @if($entryPoint->isAvailable() && (int) $entryPoint->rt->active_service_entry_points_count > 1)
                                            <span class="badge text-bg-warning">Audit: {{ $entryPoint->rt->active_service_entry_points_count }} QR aktif lama</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if ($entryPoint->isAvailable())
                                            <form method="POST" action="{{ route('admin.service-entry-points.revoke', $entryPoint) }}" onsubmit="return confirm('Nonaktifkan QR ini? QR cetak lama tidak akan berfungsi.')">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Nonaktifkan</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-secondary py-4">Belum ada QR yang diterbitkan.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
</main>
@endsection
