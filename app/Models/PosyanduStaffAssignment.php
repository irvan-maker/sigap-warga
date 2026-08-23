<?php

namespace App\Models;

use App\Enums\PosyanduStaffRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['posyandu_site_id', 'user_id', 'role', 'is_active'])]
class PosyanduStaffAssignment extends Model
{
    protected function casts(): array
    {
        return [
            'role' => PosyanduStaffRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(PosyanduSite::class, 'posyandu_site_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
