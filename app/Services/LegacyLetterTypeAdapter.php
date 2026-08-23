<?php

namespace App\Services;

use App\Enums\LetterType;
use App\Models\LetterTypeDefinition;
use Illuminate\Database\Eloquent\Collection;

final class LegacyLetterTypeAdapter
{
    public function code(LetterType $legacyType): string
    {
        return $legacyType->value;
    }

    /** @return array{code: string, name: string, description: null, is_active: true} */
    public function attributes(LetterType $legacyType): array
    {
        return [
            'code' => $this->code($legacyType),
            'name' => $legacyType->label(),
            'description' => null,
            'is_active' => true,
        ];
    }

    public function definitionFor(LetterType $legacyType): ?LetterTypeDefinition
    {
        return LetterTypeDefinition::query()
            ->where('code', $this->code($legacyType))
            ->first();
    }

    public function resolveOrCreate(LetterType $legacyType): LetterTypeDefinition
    {
        $attributes = $this->attributes($legacyType);

        return LetterTypeDefinition::query()->firstOrCreate(
            ['code' => $attributes['code']],
            $attributes,
        );
    }

    /** @return Collection<int, LetterTypeDefinition> */
    public function resolveAll(): Collection
    {
        return new Collection(array_map(
            fn (LetterType $legacyType): LetterTypeDefinition => $this->resolveOrCreate($legacyType),
            LetterType::cases(),
        ));
    }
}
