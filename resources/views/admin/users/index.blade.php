@extends('layouts.app')

@section('title', 'Kelola Akun Petugas - SIGAP WARGA')

@section('content')
    <div class="admin-dashboard min-vh-100">
        <nav class="navbar bg-white border-bottom sticky-top" aria-label="Navigasi admin">
            <div class="container py-1">
                <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-primary" href="{{ route('dashboard') }}">
                    <span class="admin-brand-mark text-white" aria-hidden="true">SW</span>
                    <span>SIGAP WARGA</span>
                </a>
                <div class="d-flex align-items-center gap-2">
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('dashboard') }}">Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline-danger btn-sm" type="submit">Keluar</button></form>
                </div>
            </div>
        </nav>

        <main class="container py-4 py-lg-5">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Pengguna</li>
                </ol>
            </nav>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
                <div><p class="admin-eyebrow mb-1">Administrasi Akses</p><h1 class="h2 fw-bold mb-1">Kelola Akun Petugas</h1><p class="text-secondary mb-0">Kelola akun dan penempatan petugas SIGAP WARGA.</p></div>
                <a class="btn btn-primary" href="{{ route('admin.users.create') }}">Tambah Akun Petugas</a>
            </div>

            <section class="card admin-panel border-0 shadow-sm mb-4" aria-labelledby="user-filter-heading">
                <div class="card-body p-4">
                    <h2 id="user-filter-heading" class="h5 fw-bold mb-3">Cari Pengguna</h2>
                    <form method="GET" action="{{ route('admin.users.index') }}" class="row g-3 align-items-end">
                        <div class="col-lg-5"><label class="form-label" for="search">Nama atau email</label><input class="form-control" id="search" name="search" type="search" value="{{ request('search') }}" placeholder="Cari nama atau email"></div>
                        <div class="col-sm-6 col-lg-2"><label class="form-label" for="role">Role</label><select class="form-select" id="role" name="role"><option value="">Semua role</option>@foreach (\App\Enums\UserRole::cases() as $role)<option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->value }}</option>@endforeach</select></div>
                        <div class="col-sm-6 col-lg-2"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">Semua status</option><option value="active" @selected(request('status') === 'active')>Aktif</option><option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option></select></div>
                        <div class="col-lg-3 d-flex gap-2"><button class="btn btn-primary flex-grow-1" type="submit">Cari</button><a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">Reset</a></div>
                    </form>
                </div>
            </section>

            <section class="card admin-panel border-0 shadow-sm" aria-labelledby="users-heading">
                <div class="card-header bg-white border-0 p-4 pb-2 d-flex justify-content-between align-items-center gap-3"><h2 id="users-heading" class="h5 fw-bold mb-0">Daftar Pengguna</h2><span class="badge rounded-pill text-bg-primary px-3 py-2">{{ number_format($users->total()) }} pengguna</span></div>
                <div class="card-body p-0 pt-2">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th class="ps-4">Pengguna</th><th>Role</th><th>Penempatan</th><th>Status</th><th class="pe-4 text-end">Aksi</th></tr></thead>
                            <tbody>
                                @forelse ($users as $managedUser)
                                    <tr class="admin-user-row" data-user-url="{{ route('admin.users.edit', $managedUser) }}" tabindex="0" aria-label="Edit pengguna {{ $managedUser->name }}">
                                        <td class="ps-4"><strong class="d-block">{{ $managedUser->name }}</strong><small class="text-secondary">{{ $managedUser->email }}</small></td>
                                        <td><span class="badge text-bg-light border">{{ $managedUser->position?->label() ?? ($managedUser->role === \App\Enums\UserRole::RW ? 'Petugas RW' : 'Petugas RT') }}</span></td>
                                        <td>{{ $managedUser->rt?->code ?? $managedUser->rw?->code ?? '—' }}</td>
                                        <td><span class="badge rounded-pill text-bg-{{ $managedUser->is_active ? 'success' : 'secondary' }}">{{ $managedUser->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                        <td class="pe-4 text-end"><a class="btn btn-outline-primary btn-sm" href="{{ route('admin.users.edit', $managedUser) }}">Edit</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center px-4 py-5"><div class="admin-empty-state mx-auto"><div class="admin-empty-icon mx-auto mb-3" aria-hidden="true">U</div><h3 class="h6 fw-bold">Pengguna tidak ditemukan</h3><p class="text-secondary small mb-0">Belum ada pengguna atau tidak ada data yang cocok dengan filter.</p></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($users->hasPages())<div class="border-top px-4 py-3">{{ $users->links('pagination::bootstrap-5') }}</div>@endif
                </div>
            </section>
        </main>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-user-url]').forEach((row) => {
            const navigate = () => window.location.assign(row.dataset.userUrl);
            row.addEventListener('click', (event) => {
                const interactive = event.target instanceof Element && event.target.closest('a, button, input, select, textarea, label');
                if (!event.defaultPrevented && !interactive && event.button === 0 && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey) navigate();
            });
            row.addEventListener('keydown', (event) => {
                if (event.target === row && event.key === 'Enter') { event.preventDefault(); navigate(); }
            });
        });
    </script>
@endpush
