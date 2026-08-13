<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['rw_id', 'code', 'name', 'whatsapp_number', 'is_active'])]
class Rt extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Rw, $this>
     */
    public function rw(): BelongsTo
    {
        return $this->belongsTo(Rw::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Citizen, $this>
     */
    public function citizens(): HasMany
    {
        return $this->hasMany(Citizen::class);
    }

    public function familyCards(): HasMany
    {
        return $this->hasMany(FamilyCard::class);
    }

    /**
     * @return HasMany<Report, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function serviceEntryPoints(): HasMany
    {
        return $this->hasMany(ServiceEntryPoint::class);
    }

    public function activeServiceEntryPoints(): HasMany
    {
        return $this->serviceEntryPoints()
            ->where('is_active', true)
            ->whereNull('revoked_at');
    }

    public function isAvailableForService(): bool
    {
        return $this->exists && $this->is_active && $this->rw?->is_active === true;
    }
}
