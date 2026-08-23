<?php

namespace App\Models;

use App\Enums\LetterRequirementEvidenceType;
use App\Models\Concerns\GuardsPublishedLetterTypeVersion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['letter_type_version_id', 'key', 'label', 'description', 'is_required', 'evidence_type', 'sequence', 'configuration'])]
class LetterRequirement extends Model
{
    use GuardsPublishedLetterTypeVersion;

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'evidence_type' => LetterRequirementEvidenceType::class,
            'sequence' => 'integer',
            'configuration' => 'array',
        ];
    }

    public function typeVersion(): BelongsTo
    {
        return $this->belongsTo(LetterTypeVersion::class, 'letter_type_version_id');
    }
}
