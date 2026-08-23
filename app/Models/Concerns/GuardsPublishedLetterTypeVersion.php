<?php

namespace App\Models\Concerns;

use App\Enums\LetterTypeVersionStatus;
use App\Models\LetterTypeVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

trait GuardsPublishedLetterTypeVersion
{
    public static function bootGuardsPublishedLetterTypeVersion(): void
    {
        static::creating(function (Model $child): void {
            $child->assertLetterTypeVersionIsMutable();
        });

        static::updating(function (Model $child): void {
            if ((int) $child->getRawOriginal('letter_type_version_id') !== (int) $child->getAttribute('letter_type_version_id')) {
                throw ValidationException::withMessages([
                    'letter_type_version_id' => 'Parent configuration tidak dapat diubah.',
                ]);
            }

            $child->assertLetterTypeVersionIsMutable();
        });

        static::deleting(function (Model $child): void {
            $child->assertLetterTypeVersionIsMutable();
        });
    }

    private function assertLetterTypeVersionIsMutable(): void
    {
        $versionId = $this->getRawOriginal('letter_type_version_id')
            ?? $this->getAttribute('letter_type_version_id');

        if ($versionId === null) {
            return;
        }

        $isPublished = LetterTypeVersion::query()
            ->whereKey($versionId)
            ->where('status', LetterTypeVersionStatus::PUBLISHED->value)
            ->exists();

        if ($isPublished) {
            throw ValidationException::withMessages([
                'configuration' => 'Published configuration bersifat immutable. Buat draft version baru untuk melakukan perubahan.',
            ]);
        }
    }
}
