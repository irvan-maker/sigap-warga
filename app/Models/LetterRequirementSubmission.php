<?php

namespace App\Models;

use App\Enums\LetterRequirementEvidenceType;
use App\Enums\LetterRequirementSubmissionStatus;
use App\Models\Concerns\GuardsImmutableLetterSubmission;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['letter_submission_id', 'letter_requirement_id', 'requirement_key', 'requirement_label', 'requirement_description', 'evidence_type', 'is_required', 'sequence', 'status', 'configuration_snapshot'])]
class LetterRequirementSubmission extends Model
{
    use GuardsImmutableLetterSubmission;

    protected $hidden = ['configuration_snapshot'];

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'evidence_type' => LetterRequirementEvidenceType::class,
            'is_required' => 'boolean',
            'sequence' => 'integer',
            'status' => LetterRequirementSubmissionStatus::class,
            'configuration_snapshot' => 'array',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(LetterSubmission::class, 'letter_submission_id');
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(LetterRequirement::class, 'letter_requirement_id');
    }

    public function evidence(): HasOne
    {
        return $this->hasOne(LetterRequirementEvidence::class);
    }

    protected function immutableLetterSubmission(): ?LetterSubmission
    {
        return LetterSubmission::query()->with('letter:id,letter_type_version_id')->find($this->letter_submission_id);
    }

    protected function assertImmutableLetterSubmissionOwnership(LetterSubmission $submission): void
    {
        $belongsToPinnedVersion = $submission->letter?->letter_type_version_id !== null
            && LetterRequirement::query()
                ->whereKey($this->letter_requirement_id)
                ->where('letter_type_version_id', $submission->letter->letter_type_version_id)
                ->exists();

        if (! $belongsToPinnedVersion) {
            throw new \LogicException('Definisi persyaratan tidak berasal dari version pengajuan yang dipin.');
        }
    }
}
