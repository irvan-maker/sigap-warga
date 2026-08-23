<?php

namespace App\Models;

use App\Enums\LetterFieldType;
use App\Models\Concerns\GuardsImmutableLetterSubmission;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['letter_submission_id', 'letter_field_definition_id', 'field_key', 'field_label', 'field_type', 'sequence', 'submitted_value'])]
class LetterFieldValue extends Model
{
    use GuardsImmutableLetterSubmission;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'field_type' => LetterFieldType::class,
            'sequence' => 'integer',
            'submitted_value' => 'json',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(LetterSubmission::class, 'letter_submission_id');
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(LetterFieldDefinition::class, 'letter_field_definition_id');
    }

    protected function immutableLetterSubmission(): ?LetterSubmission
    {
        return LetterSubmission::query()->with('letter:id,letter_type_version_id')->find($this->letter_submission_id);
    }

    protected function assertImmutableLetterSubmissionOwnership(LetterSubmission $submission): void
    {
        $belongsToPinnedVersion = $submission->letter?->letter_type_version_id !== null
            && LetterFieldDefinition::query()
                ->whereKey($this->letter_field_definition_id)
                ->where('letter_type_version_id', $submission->letter->letter_type_version_id)
                ->exists();

        if (! $belongsToPinnedVersion) {
            throw new \LogicException('Definisi field tidak berasal dari version pengajuan yang dipin.');
        }
    }
}
