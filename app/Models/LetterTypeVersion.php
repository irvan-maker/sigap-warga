<?php

namespace App\Models;

use App\Enums\LetterTypeVersionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['letter_type_id', 'version', 'status', 'published_at', 'created_by_user_id', 'configuration_snapshot'])]
class LetterTypeVersion extends Model
{
    protected $attributes = [
        'status' => LetterTypeVersionStatus::DRAFT->value,
    ];

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

    public function letters(): HasMany
    {
        return $this->hasMany(VillageLetter::class, 'letter_type_version_id');
    }
}
