<?php

namespace App\Models;

use App\Enums\LetterType;
use App\Enums\LetterTypeVersionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['code', 'name', 'description', 'is_active'])]
class LetterTypeDefinition extends Model
{
    protected $table = 'letter_types';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function legacyType(): ?LetterType
    {
        return LetterType::tryFrom($this->code);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(LetterTypeVersion::class, 'letter_type_id');
    }

    public function draftVersion(): HasOne
    {
        return $this->hasOne(LetterTypeVersion::class, 'letter_type_id')
            ->where('status', LetterTypeVersionStatus::DRAFT->value);
    }

    public function latestPublishedVersion(): HasOne
    {
        return $this->hasOne(LetterTypeVersion::class, 'letter_type_id')
            ->ofMany(
                ['version' => 'max'],
                fn ($query) => $query->where('status', LetterTypeVersionStatus::PUBLISHED->value),
            );
    }

    public function letters(): HasMany
    {
        return $this->hasMany(VillageLetter::class, 'letter_type_id');
    }
}
