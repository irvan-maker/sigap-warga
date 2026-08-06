<?php

namespace App\Http\Controllers;

use App\Enums\FamilyRelationship;
use App\Enums\UserRole;
use App\Http\Requests\IndexFamilyCardsRequest;
use App\Http\Requests\SaveCitizenRequest;
use App\Http\Requests\SaveFamilyCardRequest;
use App\Models\Citizen;
use App\Models\FamilyCard;
use App\Models\Rt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class FamilyCardController extends Controller
{
    public function index(IndexFamilyCardsRequest $request): View
    {
        Gate::authorize('viewAny', FamilyCard::class);
        $query = FamilyCard::query()
            ->with(['rt:id,rw_id,code,name', 'rt.rw:id,code,name', 'headCitizen:id,name'])
            ->withCount([
                'citizens',
                'citizens as citizens_without_nik_count' => fn (Builder $query) => $query->where(fn (Builder $query) => $query->whereNull('nik')->orWhere('nik', '')),
            ]);
        $this->scope($query, $request);
        $search = trim((string) $request->query('search'));
        $query->when($search, fn (Builder $q) => $q->where(fn (Builder $q) => $q->where('family_number', 'like', "%{$search}%")->orWhereHas('headCitizen', fn (Builder $h) => $h->where('name', 'like', "%{$search}%"))))
            ->when(in_array($request->query('status'), ['active', 'inactive'], true), fn (Builder $q) => $q->where('is_active', $request->query('status') === 'active'))
            ->when($request->query('completeness') === 'without_head', fn (Builder $q) => $q->whereNull('head_citizen_id'))
            ->when($request->filled('rt_id'), fn (Builder $q) => $q->where('rt_id', (int) $request->query('rt_id')))
            ->when($request->filled('rw_id'), fn (Builder $q) => $q->whereHas('rt', fn (Builder $rt) => $rt->where('rw_id', (int) $request->query('rw_id'))));

        return view('master.family-cards.index', ['familyCards' => $query->orderBy('family_number')->paginate(15)->withQueryString(), 'rts' => $this->rts($request), 'routePrefix' => $this->prefix($request)]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', FamilyCard::class);

        return view('master.family-cards.form', ['familyCard' => new FamilyCard, 'rts' => $this->rts($request), 'routePrefix' => $this->prefix($request)]);
    }

    public function store(SaveFamilyCardRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $rtId = $request->user()->rt_id ?? (int) $data['region_rt_id'];
        unset($data['region_rt_id']);
        $card = DB::transaction(fn () => FamilyCard::query()->create([...$data, 'rt_id' => $rtId, 'is_active' => true]), 3);

        return redirect()->route($this->prefix($request).'.family-cards.show', $card)->with('status', 'Kartu Keluarga berhasil ditambahkan. Tambahkan anggota lalu tetapkan kepala keluarga.');
    }

    public function show(Request $request, FamilyCard $familyCard): View
    {
        Gate::authorize('view', $familyCard);
        $familyCard->load(['rt:id,rw_id,code,name', 'rt.rw:id,code,name', 'headCitizen:id,name,is_active', 'citizens' => fn ($query) => $query->orderBy('name')])->loadCount('citizens');

        return view('master.family-cards.show', ['familyCard' => $familyCard, 'routePrefix' => $this->prefix($request)]);
    }

    public function createMember(Request $request, FamilyCard $familyCard): View
    {
        Gate::authorize('update', $familyCard);

        return view('master.citizens.form', ['citizen' => new Citizen, 'contextCard' => $familyCard->load('rt.rw'), 'cards' => collect(), 'rts' => collect(), 'routePrefix' => $this->prefix($request)]);
    }

    public function storeMember(SaveCitizenRequest $request, FamilyCard $familyCard): RedirectResponse
    {
        $data = $request->validated();
        unset($data['region_rt_id']);
        $data['family_card_id'] = $familyCard->id;
        $data['rt_id'] = $familyCard->rt_id;
        $data['is_active'] = true;
        DB::transaction(fn () => Citizen::query()->create($data), 3);

        return redirect()->route($this->prefix($request).'.family-cards.show', $familyCard)->with('status', 'Anggota keluarga berhasil ditambahkan.');
    }

    public function setHead(Request $request, FamilyCard $familyCard, Citizen $citizen): RedirectResponse
    {
        Gate::authorize('update', $familyCard);
        Gate::authorize('update', $citizen);

        DB::transaction(function () use ($familyCard, $citizen): void {
            $lockedCard = FamilyCard::query()->lockForUpdate()->findOrFail($familyCard->id);
            $lockedCitizen = Citizen::query()->lockForUpdate()->findOrFail($citizen->id);
            abort_unless($lockedCitizen->family_card_id === $lockedCard->id && $lockedCitizen->rt_id === $lockedCard->rt_id, 422, 'Warga bukan anggota Kartu Keluarga ini.');
            Citizen::query()->where('family_card_id', $lockedCard->id)->where('family_relationship', FamilyRelationship::HEAD)->whereKeyNot($lockedCitizen->id)->update(['family_relationship' => FamilyRelationship::OTHER]);
            $lockedCard->update(['head_citizen_id' => $lockedCitizen->id]);
            $lockedCitizen->update(['family_relationship' => FamilyRelationship::HEAD]);
        }, 3);

        return redirect()->route($this->prefix($request).'.family-cards.show', $familyCard)->with('status', 'Kepala keluarga berhasil ditetapkan.');
    }

    public function edit(Request $request, FamilyCard $familyCard): View
    {
        Gate::authorize('update', $familyCard);

        return view('master.family-cards.form', ['familyCard' => $familyCard, 'rts' => collect(), 'routePrefix' => $this->prefix($request)]);
    }

    public function update(SaveFamilyCardRequest $request, FamilyCard $familyCard): RedirectResponse
    {
        $data = $request->validated();
        unset($data['region_rt_id']);
        DB::transaction(fn () => FamilyCard::query()->lockForUpdate()->findOrFail($familyCard->id)->update($data), 3);

        return back()->with('status', 'Kartu Keluarga berhasil diperbarui.');
    }

    public function toggleActive(Request $request, FamilyCard $familyCard): RedirectResponse
    {
        Gate::authorize('toggleActive', $familyCard);
        DB::transaction(function () use ($familyCard): void {
            $locked = FamilyCard::query()->lockForUpdate()->findOrFail($familyCard->id);
            $locked->update(['is_active' => ! $locked->is_active]);
        }, 3);

        return back()->with('status', 'Status Kartu Keluarga berhasil diperbarui.');
    }

    private function scope(Builder $query, Request $request): void
    {
        match ($request->user()->role) {
            UserRole::RT => $query->where('rt_id', $request->user()->rt_id), UserRole::RW => $query->whereHas('rt', fn (Builder $q) => $q->where('rw_id', $request->user()->rw_id)), default => null
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
