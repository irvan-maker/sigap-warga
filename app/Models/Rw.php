<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable(['code', 'name', 'is_active'])]
class Rw extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Rt, $this>
     */
    public function rts(): HasMany
    {
        return $this->hasMany(Rt::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasManyThrough<Report, Rt, $this>
     */
    public function reports(): HasManyThrough
    {
        return $this->hasManyThrough(Report::class, Rt::class);
    }

    public function citizens(): HasManyThrough
    {
        return $this->hasManyThrough(Citizen::class, Rt::class);
    }

    public function familyCards(): HasManyThrough
    {
        return $this->hasManyThrough(FamilyCard::class, Rt::class);
    }
}
