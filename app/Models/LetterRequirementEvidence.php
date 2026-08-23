<?php

namespace App\Models;

use App\Models\Concerns\GuardsImmutableLetterSubmission;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['letter_requirement_submission_id', 'disk', 'path', 'stored_name', 'original_name', 'mime_type', 'size', 'sha256'])]
class LetterRequirementEvidence extends Model
{
    use GuardsImmutableLetterSubmission;

    protected $table = 'letter_requirement_evidences';

    protected $hidden = [
        'disk',
        'path',
        'stored_name',
        'original_name',
        'sha256',
    ];

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    public function requirementSubmission(): BelongsTo
    {
        return $this->belongsTo(LetterRequirementSubmission::class);
    }

    protected function immutableLetterSubmission(): ?LetterSubmission
    {
        return LetterRequirementSubmission::query()
            ->with('submission')
            ->find($this->letter_requirement_submission_id)
            ?->submission;
    }
}
