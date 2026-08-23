<?php

namespace App\Models;

use App\Enums\LetterFieldDataSource;
use App\Enums\LetterFieldType;
use App\Models\Concerns\GuardsPublishedLetterTypeVersion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['letter_type_version_id', 'key', 'label', 'field_type', 'is_required', 'sequence', 'data_source', 'validation', 'configuration'])]
class LetterFieldDefinition extends Model
{
    use GuardsPublishedLetterTypeVersion;

    protected function casts(): array
    {
        return [
            'field_type' => LetterFieldType::class,
            'is_required' => 'boolean',
            'sequence' => 'integer',
            'data_source' => LetterFieldDataSource::class,
            'validation' => 'array',
            'configuration' => 'array',
        ];
    }

    public function typeVersion(): BelongsTo
    {
        return $this->belongsTo(LetterTypeVersion::class, 'letter_type_version_id');
    }
}
