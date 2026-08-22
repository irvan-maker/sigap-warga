<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['letter_type_version_id', 'sequence', 'action', 'actor_scope', 'is_required', 'configuration'])]
class LetterWorkflowStep extends Model
{
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'is_required' => 'boolean',
            'configuration' => 'array',
        ];
    }

    public function typeVersion(): BelongsTo
    {
        return $this->belongsTo(LetterTypeVersion::class, 'letter_type_version_id');
    }
}
