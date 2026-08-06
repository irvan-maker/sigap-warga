<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexManagedRtsRequest;
use App\Http\Requests\SaveManagedRtRequest;
use App\Http\Requests\ToggleManagedRtStatusRequest;
use App\Models\Rt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RwRtController extends Controller
{
    public function index(IndexManagedRtsRequest $request): View
    {
        $filters = $request->validated();
        $rts = Rt::query()->where('rw_id', $request->user()->rw_id)
            ->withCount(['citizens', 'reports'])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")))
            ->when(isset($filters['status']), fn (Builder $query) => $query->where('is_active', $filters['status'] === 'active'))
            ->orderBy('code')->paginate(15)->withQueryString();

        return view('rw.rts.index', ['rts' => $rts]);
    }

    public function create(): View
    {
        Gate::authorize('create', Rt::class);

        return view('rw.rts.create');
    }

    public function store(SaveManagedRtRequest $request): RedirectResponse
    {
        $rt = DB::transaction(fn () => Rt::query()->create([...$request->validated(), 'rw_id' => $request->user()->rw_id, 'is_active' => true]), 3);

        return redirect()->route('rw.rts.edit', $rt)->with('status', 'RT berhasil ditambahkan.');
    }

    public function edit(Rt $rt): View
    {
        Gate::authorize('update', $rt);

        return view('rw.rts.edit', ['managedRt' => $rt->loadCount(['citizens', 'reports'])]);
    }

    public function update(SaveManagedRtRequest $request, Rt $rt): RedirectResponse
    {
        DB::transaction(function () use ($request, $rt): void {
            $locked = Rt::query()->lockForUpdate()->findOrFail($rt->id);
            Gate::authorize('update', $locked);
            $locked->update([...$request->validated(), 'rw_id' => $request->user()->rw_id]);
        }, 3);

        return redirect()->route('rw.rts.edit', $rt)->with('status', 'Data RT berhasil diperbarui.');
    }

    public function toggleActive(ToggleManagedRtStatusRequest $request, Rt $rt): RedirectResponse
    {
        DB::transaction(function () use ($rt): void {
            $locked = Rt::query()->lockForUpdate()->findOrFail($rt->id);
            Gate::authorize('toggleActive', $locked);
            if ($locked->is_active && $locked->users()->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['status' => 'RT tidak dapat dinonaktifkan selama masih memiliki akun petugas aktif.']);
            }
            $locked->update(['is_active' => ! $locked->is_active]);
        }, 3);

        return redirect()->route('rw.rts.edit', $rt)->with('status', 'Status RT berhasil diperbarui.');
    }
}
