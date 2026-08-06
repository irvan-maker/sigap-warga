<?php

namespace App\Http\Controllers;

use App\Enums\FamilyRelationship;
use App\Enums\MaritalStatus;
use App\Http\Requests\StoreHouseholdCensusRequest;
use App\Services\HouseholdCensusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HouseholdCensusController extends Controller
{
    public function create(): View
    {
        return view('rt.household-census.create', [
            'relationships' => array_filter(FamilyRelationship::cases(), fn (FamilyRelationship $relationship) => $relationship !== FamilyRelationship::HEAD),
            'maritalStatuses' => MaritalStatus::cases(),
        ]);
    }

    public function store(StoreHouseholdCensusRequest $request, HouseholdCensusService $service): RedirectResponse
    {
        $card = $service->create($request->user(), $request->validated());

        return redirect()->route('rt.family-cards.show', $card)->with('status', 'Sensus keluarga berhasil disimpan.');
    }
}
