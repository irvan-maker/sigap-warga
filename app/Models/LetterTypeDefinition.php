<?php

namespace App\Models;

use App\Enums\LetterType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function letters(): HasMany
    {
        return $this->hasMany(VillageLetter::class, 'letter_type_id');
    }
}
