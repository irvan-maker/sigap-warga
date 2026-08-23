<?php

namespace App\Models;

use App\Enums\LetterTypeVersionStatus;
use Closure;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

#[Fillable(['letter_type_id', 'version', 'status', 'published_at', 'created_by_user_id', 'configuration_snapshot'])]
class LetterTypeVersion extends Model
{
    private bool $publishingValidatedConfiguration = false;

    protected $attributes = [
        'status' => LetterTypeVersionStatus::DRAFT->value,
    ];

    protected static function booted(): void
    {
        static::creating(function (LetterTypeVersion $version): void {
            if ($version->status !== LetterTypeVersionStatus::DRAFT || $version->letter_type_id === null) {
                return;
            }

            $draftExists = self::query()
                ->where('letter_type_id', $version->letter_type_id)
                ->where('status', LetterTypeVersionStatus::DRAFT->value)
                ->exists();

            if ($draftExists) {
                throw ValidationException::withMessages([
                    'version' => 'Jenis surat ini masih mempunyai draft version aktif.',
                ]);
            }
        });

        static::updating(function (LetterTypeVersion $version): void {
            if (! $version->isValidPublishTransition()) {
                throw ValidationException::withMessages([
                    'version' => 'Lifecycle configuration version hanya dapat diubah melalui proses publish tervalidasi.',
                ]);
            }
        });

        static::deleting(function (LetterTypeVersion $version): void {
            $isPublished = self::query()
                ->whereKey($version->getKey())
                ->where('status', LetterTypeVersionStatus::PUBLISHED->value)
                ->exists();

            if ($isPublished) {
                throw ValidationException::withMessages([
                    'version' => 'Published version tidak dapat dihapus. Buat version baru untuk perubahan berikutnya.',
                ]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'status' => LetterTypeVersionStatus::class,
            'published_at' => 'datetime',
            'configuration_snapshot' => 'array',
        ];
    }

    public function typeDefinition(): BelongsTo
    {
        return $this->belongsTo(LetterTypeDefinition::class, 'letter_type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function workflowSteps(): HasMany
    {
        return $this->hasMany(LetterWorkflowStep::class)->orderBy('sequence');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(LetterRequirement::class)->orderBy('sequence');
    }

    public function fieldDefinitions(): HasMany
    {
        return $this->hasMany(LetterFieldDefinition::class)->orderBy('sequence');
    }

    public function letters(): HasMany
    {
        return $this->hasMany(VillageLetter::class, 'letter_type_version_id');
    }

    public function isDraft(): bool
    {
        return $this->status === LetterTypeVersionStatus::DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === LetterTypeVersionStatus::PUBLISHED;
    }

    /**
     * @param  Closure(mixed): array<string, mixed>  $snapshotFactory
     */
    public function publishValidatedConfiguration(Closure $snapshotFactory): void
    {
        $this->refresh();

        if (! $this->isDraft()) {
            throw ValidationException::withMessages([
                'version' => 'Hanya configuration version berstatus draft yang dapat dipublish.',
            ]);
        }

        $publishedAt = now();
        $snapshot = $snapshotFactory($publishedAt);

        $this->publishingValidatedConfiguration = true;

        try {
            $this->forceFill([
                'status' => LetterTypeVersionStatus::PUBLISHED,
                'published_at' => $publishedAt,
                'configuration_snapshot' => $snapshot,
            ])->save();
        } finally {
            $this->publishingValidatedConfiguration = false;
        }
    }

    private function isValidPublishTransition(): bool
    {
        if (! $this->publishingValidatedConfiguration
            || $this->getRawOriginal('status') !== LetterTypeVersionStatus::DRAFT->value
            || $this->status !== LetterTypeVersionStatus::PUBLISHED
            || $this->published_at === null
            || ! is_array($this->configuration_snapshot)) {
            return false;
        }

        $requiredChanges = ['status', 'published_at', 'configuration_snapshot'];
        $dirty = array_keys($this->getDirty());

        return array_diff($dirty, $requiredChanges) === []
            && array_diff($requiredChanges, $dirty) === [];
    }
}
