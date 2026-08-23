<?php

namespace App\Models;

use App\Enums\LetterWorkflowAction;
use App\Enums\LetterWorkflowActorScope;
use App\Enums\UserRole;
use App\Enums\VillagePosition;
use App\Models\Concerns\GuardsPublishedLetterTypeVersion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['letter_type_version_id', 'sequence', 'action', 'actor_scope', 'actor_role', 'village_position', 'is_required', 'configuration'])]
class LetterWorkflowStep extends Model
{
    use GuardsPublishedLetterTypeVersion;

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'action' => LetterWorkflowAction::class,
            'actor_scope' => LetterWorkflowActorScope::class,
            'actor_role' => UserRole::class,
            'village_position' => VillagePosition::class,
            'is_required' => 'boolean',
            'configuration' => 'array',
        ];
    }

    public function typeVersion(): BelongsTo
    {
        return $this->belongsTo(LetterTypeVersion::class, 'letter_type_version_id');
    }
}
