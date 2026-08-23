<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['posyandu_site_id', 'created_by_user_id', 'service_date', 'starts_at', 'ends_at', 'notes'])]
class PosyanduSchedule extends Model
{
    protected function casts(): array
    {
        return [
            'service_date' => 'immutable_date',
            'notes' => 'encrypted',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(PosyanduSite::class, 'posyandu_site_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
