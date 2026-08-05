<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexManagedRwsRequest;
use App\Http\Requests\SaveManagedRwRequest;
use App\Http\Requests\ToggleManagedRwStatusRequest;
use App\Models\Rw;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class KelurahanRwController extends Controller
{
    public function index(IndexManagedRwsRequest $request): View
    {
        $filters = $request->validated();
        $rws = Rw::query()
            ->withCount(['rts', 'rts as active_rts_count' => fn (Builder $query) => $query->where('is_active', true)])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")))
            ->when(isset($filters['status']), fn (Builder $query) => $query->where('is_active', $filters['status'] === 'active'))
            ->orderBy('code')->paginate(15)->withQueryString();

        return view('kelurahan.rws.index', ['rws' => $rws]);
    }

    public function create(): View
    {
        Gate::authorize('create', Rw::class);

        return view('kelurahan.rws.create');
    }

    public function store(SaveManagedRwRequest $request): RedirectResponse
    {
        $rw = DB::transaction(fn () => Rw::query()->create([...$request->validated(), 'is_active' => true]), 3);

        return redirect()->route('kelurahan.rws.edit', $rw)->with('status', 'RW berhasil ditambahkan.');
    }

    public function edit(Rw $rw): View
    {
        Gate::authorize('update', $rw);

        return view('kelurahan.rws.edit', ['managedRw' => $rw->loadCount(['rts'])]);
    }

    public function update(SaveManagedRwRequest $request, Rw $rw): RedirectResponse
    {
        DB::transaction(fn () => Rw::query()->lockForUpdate()->findOrFail($rw->id)->update($request->validated()), 3);

        return redirect()->route('kelurahan.rws.edit', $rw)->with('status', 'Data RW berhasil diperbarui.');
    }

    public function toggleActive(ToggleManagedRwStatusRequest $request, Rw $rw): RedirectResponse
    {
        DB::transaction(function () use ($rw): void {
            $locked = Rw::query()->lockForUpdate()->findOrFail($rw->id);
            if ($locked->is_active && ($locked->rts()->where('is_active', true)->exists() || $locked->users()->where('is_active', true)->exists())) {
                throw ValidationException::withMessages(['status' => 'RW tidak dapat dinonaktifkan selama masih memiliki RT aktif atau akun wilayah aktif.']);
            }
            $locked->update(['is_active' => ! $locked->is_active]);
        }, 3);

        return redirect()->route('kelurahan.rws.edit', $rw)->with('status', 'Status RW berhasil diperbarui.');
    }
}
