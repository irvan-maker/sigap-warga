<?php

namespace App\Services;

use App\Enums\FamilyRelationship;
use App\Models\Citizen;
use App\Models\FamilyCard;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class HouseholdCensusService
{
    public function create(User $rtUser, array $data): FamilyCard
    {
        return DB::transaction(function () use ($rtUser, $data): FamilyCard {
            $card = FamilyCard::query()->create([
                'rt_id' => $rtUser->rt_id,
                'family_number' => $data['family_number'],
                'address' => $data['address'],
                'is_active' => true,
            ]);

            $head = $this->createCitizen($card, $data['head'], FamilyRelationship::OTHER);
            $card->update(['head_citizen_id' => $head->id]);
            $head->update(['family_relationship' => FamilyRelationship::HEAD]);

            foreach ($data['members'] ?? [] as $member) {
                $this->createCitizen($card, $member, FamilyRelationship::from($member['family_relationship']));
            }

            return $card;
        }, 3);
    }

    private function createCitizen(FamilyCard $card, array $person, FamilyRelationship $relationship): Citizen
    {
        return Citizen::query()->create([
            ...Arr::only($person, ['name', 'nik', 'phone', 'phone_normalized', 'birth_place', 'birth_date', 'gender', 'marital_status']),
            'rt_id' => $card->rt_id,
            'family_card_id' => $card->id,
            'family_relationship' => $relationship,
            'address' => $card->address,
            'is_active' => true,
        ]);
    }
}
