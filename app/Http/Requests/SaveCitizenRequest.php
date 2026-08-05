<?php

namespace App\Http\Requests;

use App\Enums\FamilyRelationship;
use App\Models\Citizen;
use App\Models\FamilyCard;
use App\Support\PhoneNumberNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveCitizenRequest extends FormRequest
{
    public function authorize(): bool
    {
        $citizen = $this->route('citizen');

        if ($this->route('familyCard') instanceof FamilyCard) {
            return Gate::allows('create', Citizen::class)
                && Gate::allows('update', $this->route('familyCard'));
        }

        return $citizen instanceof Citizen ? Gate::allows('update', $citizen) : Gate::allows('create', Citizen::class);
    }

    public function rules(): array
    {
        $citizen = $this->route('citizen');
        $contextCard = $this->route('familyCard');
        $rtId = $contextCard instanceof FamilyCard
            ? $contextCard->rt_id
            : ($citizen instanceof Citizen ? $citizen->rt_id : ($this->user()->rt_id ?? (int) $this->input('region_rt_id')));

        return [
            'region_rt_id' => [$this->user()->rt_id === null && ! ($citizen instanceof Citizen) && ! ($contextCard instanceof FamilyCard) ? 'required' : 'nullable', 'integer', Rule::exists('rts', 'id')],
            'family_card_id' => [$contextCard instanceof FamilyCard ? 'nullable' : 'nullable', Rule::exists('family_cards', 'id')->where(fn ($query) => $query->where('rt_id', $rtId))],
            'family_relationship' => ['nullable', Rule::enum(FamilyRelationship::class), Rule::notIn([FamilyRelationship::HEAD->value])],
            'nik' => ['nullable', 'digits:16', Rule::unique('citizens', 'nik')->ignore($citizen instanceof Citizen ? $citizen : null)],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'phone_normalized' => ['nullable', 'regex:/^62\d{8,13}$/', Rule::unique('citizens', 'phone_normalized')->ignore($citizen instanceof Citizen ? $citizen : null)],
            'gender' => ['nullable', Rule::in(['L', 'P'])],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'address' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $nullable = fn (string $key): ?string => trim((string) $this->input($key)) ?: null;
        $contextCard = $this->route('familyCard');
        $this->merge([
            'family_card_id' => $contextCard instanceof FamilyCard ? $contextCard->id : ($this->filled('family_card_id') ? (int) $this->input('family_card_id') : null),
            'family_relationship' => $nullable('family_relationship'),
            'nik' => $nullable('nik'), 'name' => trim((string) $this->input('name')),
            'phone' => $nullable('phone'),
            'phone_normalized' => $this->filled('phone') ? app(PhoneNumberNormalizer::class)->normalize((string) $this->input('phone')) : null,
            'gender' => $nullable('gender'), 'birth_place' => $nullable('birth_place'), 'address' => $nullable('address'),
        ]);
    }
}
