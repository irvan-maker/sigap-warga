<?php

namespace App\Models;

use App\Enums\PosyanduLifeCycleGroup;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'posyandu_site_id',
    'citizen_id',
    'recorded_by_user_id',
    'visited_at',
    'life_cycle_group',
    'weight_kg',
    'height_cm',
    'notes',
    'follow_up',
    'referral_required',
])]
class PosyanduVisit extends Model
{
    protected function casts(): array
    {
        return [
            'visited_at' => 'immutable_datetime',
            'life_cycle_group' => PosyanduLifeCycleGroup::class,
            'weight_kg' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'notes' => 'encrypted',
            'follow_up' => 'encrypted',
            'referral_required' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(PosyanduSite::class, 'posyandu_site_id');
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
