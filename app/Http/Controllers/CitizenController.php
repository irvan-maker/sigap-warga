<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\SaveCitizenRequest;
use App\Models\Citizen;
use App\Models\FamilyCard;
use App\Models\Rt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CitizenController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Citizen::class);
        $query = Citizen::query()->with(['rt:id,rw_id,code,name', 'rt.rw:id,code,name', 'familyCard:id,family_number']);
        $this->scope($query, $request);
        $search = trim((string) $request->query('search'));
        $query->when($search, fn (Builder $q) => $q->where(fn (Builder $q) => $q->where('name', 'like', "%{$search}%")->orWhere('nik', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")->orWhere('phone_normalized', 'like', "%{$search}%")))
            ->when(in_array($request->query('status'), ['active', 'inactive'], true), fn (Builder $q) => $q->where('is_active', $request->query('status') === 'active'))
            ->when($request->filled('rt_id'), fn (Builder $q) => $q->where('rt_id', (int) $request->query('rt_id')))
            ->when($request->filled('rw_id'), fn (Builder $q) => $q->whereHas('rt', fn (Builder $rt) => $rt->where('rw_id', (int) $request->query('rw_id'))));

        return view('master.citizens.index', ['citizens' => $query->orderBy('name')->paginate(15)->withQueryString(), 'rts' => $this->rts($request), 'routePrefix' => $this->prefix($request)]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Citizen::class);

        return view('master.citizens.form', ['citizen' => new Citizen, 'contextCard' => null, 'cards' => $request->user()->rt_id ? FamilyCard::query()->where('rt_id', $request->user()->rt_id)->orderBy('family_number')->get() : collect(), 'rts' => $this->rts($request), 'routePrefix' => $this->prefix($request)]);
    }

    public function store(SaveCitizenRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $rtId = $request->user()->rt_id ?? (int) $data['region_rt_id'];
        unset($data['region_rt_id']);
        $citizen = DB::transaction(fn () => Citizen::query()->create([...$data, 'rt_id' => $rtId, 'is_active' => true]), 3);

        return redirect()->route($this->prefix($request).'.citizens.edit', $citizen)->with('status', 'Data warga berhasil ditambahkan.');
    }

    public function edit(Request $request, Citizen $citizen): View
    {
        Gate::authorize('update', $citizen);

        return view('master.citizens.form', ['citizen' => $citizen, 'contextCard' => null, 'cards' => FamilyCard::query()->where('rt_id', $citizen->rt_id)->orderBy('family_number')->get(), 'rts' => collect(), 'routePrefix' => $this->prefix($request)]);
    }

    public function show(Request $request, Citizen $citizen): View
    {
        Gate::authorize('view', $citizen);
        $citizen->load(['rt:id,rw_id,code,name', 'rt.rw:id,code,name', 'familyCard:id,family_number,head_citizen_id,address', 'familyCard.headCitizen:id,name', 'reports' => fn ($query) => $query->latest('reported_at')->latest('id')]);

        return view('master.citizens.show', ['citizen' => $citizen, 'routePrefix' => $this->prefix($request)]);
    }

    public function update(SaveCitizenRequest $request, Citizen $citizen): RedirectResponse
    {
        $data = $request->validated();
        unset($data['region_rt_id']);
        DB::transaction(fn () => Citizen::query()->lockForUpdate()->findOrFail($citizen->id)->update($data), 3);

        return back()->with('status', 'Data warga berhasil diperbarui.');
    }

    public function toggleActive(Request $request, Citizen $citizen): RedirectResponse
    {
        Gate::authorize('toggleActive', $citizen);
        DB::transaction(function () use ($citizen): void {
            $locked = Citizen::query()->lockForUpdate()->findOrFail($citizen->id);
            $locked->update(['is_active' => ! $locked->is_active]);
        }, 3);

        return back()->with('status', 'Status warga berhasil diperbarui.');
    }

    private function scope(Builder $query, Request $request): void
    {
        match ($request->user()->role) {
            UserRole::RT => $query->where('rt_id', $request->user()->rt_id),
            UserRole::RW => $query->whereHas('rt', fn (Builder $q) => $q->where('rw_id', $request->user()->rw_id)),
            default => null,
        };
    }

    private function rts(Request $request)
    {
        return Rt::query()->with('rw:id,code')->when($request->user()->role === UserRole::RW, fn (Builder $q) => $q->where('rw_id', $request->user()->rw_id))->when($request->user()->role === UserRole::RT, fn (Builder $q) => $q->whereKey($request->user()->rt_id))->orderBy('rw_id')->orderBy('code')->get();
    }

    private function prefix(Request $request): string
    {
        return $request->routeIs('rt.*') ? 'rt' : ($request->routeIs('rw.*') ? 'rw' : 'kelurahan');
    }
}
