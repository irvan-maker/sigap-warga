<?php

namespace App\Models;

use App\Enums\LetterStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['village_letter_id', 'user_id', 'old_status', 'new_status', 'note'])]
class VillageLetterHistory extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['old_status' => LetterStatus::class, 'new_status' => LetterStatus::class];
    }

    public function letter(): BelongsTo
    {
        return $this->belongsTo(VillageLetter::class, 'village_letter_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
