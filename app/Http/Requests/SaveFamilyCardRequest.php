<?php

namespace App\Http\Requests;

use App\Models\FamilyCard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveFamilyCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        $card = $this->route('familyCard');

        return $card instanceof FamilyCard ? Gate::allows('update', $card) : Gate::allows('create', FamilyCard::class);
    }

    public function rules(): array
    {
        $card = $this->route('familyCard');
        $rtId = $card instanceof FamilyCard ? $card->rt_id : ($this->user()->rt_id ?? (int) $this->input('region_rt_id'));

        return [
            'region_rt_id' => [$this->user()->rt_id === null && ! ($card instanceof FamilyCard) ? 'required' : 'nullable', 'integer', Rule::exists('rts', 'id')],
            'family_number' => ['required', 'digits:16', Rule::unique('family_cards')->ignore($card instanceof FamilyCard ? $card : null)],
            'address' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['family_number' => preg_replace('/\D+/', '', (string) $this->input('family_number')), 'address' => trim((string) $this->input('address')) ?: null]);
    }
}
