<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['rt_id', 'name', 'address', 'is_active'])]
class PosyanduSite extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function rt(): BelongsTo
    {
        return $this->belongsTo(Rt::class);
    }

    public function staffAssignments(): HasMany
    {
        return $this->hasMany(PosyanduStaffAssignment::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(PosyanduSchedule::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(PosyanduVisit::class);
    }
}
