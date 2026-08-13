<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'report_id',
    'original_name',
    'stored_name',
    'path',
    'mime_type',
    'size',
    'disk',
    'is_public',
])]
class ReportAttachment extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    /**
     * @return BelongsTo<Report, $this>
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}
