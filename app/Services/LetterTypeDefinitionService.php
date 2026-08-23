<?php

namespace App\Services;

use App\Enums\LetterTypeVersionStatus;
use App\Models\LetterTypeDefinition;
use App\Models\LetterTypeVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LetterTypeDefinitionService
{
    /** @return array{LetterTypeDefinition, LetterTypeVersion} */
    public function createWithDraft(array $attributes, User $creator): array
    {
        return DB::transaction(function () use ($attributes, $creator): array {
            $letterType = LetterTypeDefinition::query()->create($attributes);
            $version = $letterType->versions()->create([
                'version' => 1,
                'status' => LetterTypeVersionStatus::DRAFT,
                'created_by_user_id' => $creator->id,
            ]);

            return [$letterType, $version];
        }, 3);
    }

    public function update(LetterTypeDefinition $letterType, array $attributes): LetterTypeDefinition
    {
        return DB::transaction(function () use ($letterType, $attributes): LetterTypeDefinition {
            $locked = LetterTypeDefinition::query()->lockForUpdate()->findOrFail($letterType->id);

            if ($attributes['code'] !== $locked->code && ! $this->codeCanChange($locked)) {
                throw ValidationException::withMessages([
                    'code' => 'Kode merupakan identifier stabil dan tidak dapat diubah setelah digunakan atau dipublish.',
                ]);
            }

            $locked->update($attributes);

            return $locked->refresh();
        }, 3);
    }

    private function codeCanChange(LetterTypeDefinition $letterType): bool
    {
        if ($letterType->legacyType() !== null) {
            return false;
        }

        return ! $letterType->versions()
            ->where('status', LetterTypeVersionStatus::PUBLISHED->value)
            ->exists()
            && ! $letterType->letters()->exists();
    }
}
