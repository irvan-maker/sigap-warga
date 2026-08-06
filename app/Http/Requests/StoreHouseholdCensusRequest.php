<?php

namespace App\Http\Requests;

use App\Enums\FamilyRelationship;
use App\Enums\MaritalStatus;
use App\Enums\UserRole;
use App\Support\PhoneNumberNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreHouseholdCensusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active === true
            && $this->user()->role === UserRole::RT
            && $this->user()->rt_id !== null;
    }

    public function rules(): array
    {
        $relationships = array_map(fn (FamilyRelationship $relationship) => $relationship->value, array_filter(FamilyRelationship::cases(), fn (FamilyRelationship $relationship) => $relationship !== FamilyRelationship::HEAD));

        return [
            'family_number' => ['required', 'digits:16', Rule::unique('family_cards', 'family_number')],
            'address' => ['required', 'string', 'max:2000'],
            'head.name' => ['required', 'string', 'max:255'],
            'head.nik' => ['nullable', 'digits:16', Rule::unique('citizens', 'nik')],
            'head.phone' => ['nullable', 'string', 'max:30'],
            'head.phone_normalized' => ['nullable', 'regex:/^62\d{8,13}$/', Rule::unique('citizens', 'phone_normalized')],
            'head.birth_place' => ['nullable', 'string', 'max:255'],
            'head.birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'head.gender' => ['nullable', Rule::in(['L', 'P'])],
            'head.marital_status' => ['nullable', Rule::enum(MaritalStatus::class)],
            'members' => ['array'],
            'members.*.name' => ['required', 'string', 'max:255'],
            'members.*.nik' => ['nullable', 'digits:16', 'distinct', Rule::unique('citizens', 'nik')],
            'members.*.phone' => ['nullable', 'string', 'max:30'],
            'members.*.phone_normalized' => ['nullable', 'regex:/^62\d{8,13}$/', 'distinct', Rule::unique('citizens', 'phone_normalized')],
            'members.*.birth_place' => ['nullable', 'string', 'max:255'],
            'members.*.birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'members.*.gender' => ['nullable', Rule::in(['L', 'P'])],
            'members.*.family_relationship' => ['required', Rule::in($relationships)],
            'members.*.marital_status' => ['nullable', Rule::enum(MaritalStatus::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalizer = app(PhoneNumberNormalizer::class);
        $clean = function (array $person) use ($normalizer): array {
            foreach (['name', 'nik', 'phone', 'birth_place', 'birth_date', 'gender', 'family_relationship', 'marital_status'] as $key) {
                $person[$key] = trim((string) ($person[$key] ?? '')) ?: null;
            }
            $person['phone_normalized'] = $person['phone'] ? $normalizer->normalize($person['phone']) : null;

            return $person;
        };
        $members = array_values(array_filter(array_map($clean, (array) $this->input('members', [])), fn (array $member) => collect($member)->except('phone_normalized')->filter(fn ($value) => $value !== null)->isNotEmpty()));
        $head = $clean((array) $this->input('head', []));

        $this->merge([
            'family_number' => trim((string) $this->input('family_number')),
            'address' => trim((string) $this->input('address')),
            'head' => $head,
            'members' => $members,
        ]);
    }

    public function messages(): array
    {
        return ['members.*.name.required' => 'Nama lengkap anggota wajib diisi.', 'members.*.family_relationship.required' => 'Hubungan anggota wajib dipilih.'];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $headNik = data_get($this->input('head'), 'nik');
            if ($headNik && collect($this->input('members', []))->contains(fn (array $member) => ($member['nik'] ?? null) === $headNik)) {
                $validator->errors()->add('head.nik', 'NIK kepala keluarga tidak boleh sama dengan NIK anggota.');
            }
        });
    }
}
