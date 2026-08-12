<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['token_hash', 'rt_id', 'label', 'is_active', 'revoked_at'])]
class ServiceEntryPoint extends Model
{
    protected $hidden = ['token_hash'];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'revoked_at' => 'immutable_datetime',
        ];
    }

    public function rt(): BelongsTo
    {
        return $this->belongsTo(Rt::class);
    }

    public function handoffs(): HasMany
    {
        return $this->hasMany(ServiceHandoff::class);
    }

    public function isAvailable(): bool
    {
        return $this->is_active && $this->revoked_at === null && $this->rt?->is_active === true;
    }
}
