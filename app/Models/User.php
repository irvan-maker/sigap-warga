<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\VillagePosition;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use LogicException;

#[Fillable(['name', 'email', 'password', 'role', 'position', 'is_active', 'rw_id', 'rt_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if ($user->role === UserRole::RW || $user->role === UserRole::RT) {
                $user->position = null;
            }
            if (! $user->hasValidRegionAssignment()) {
                throw new LogicException('The selected region does not match the user role.');
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'role' => UserRole::class,
            'position' => VillagePosition::class,
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
     * @return BelongsTo<Rt, $this>
     */
    public function rt(): BelongsTo
    {
        return $this->belongsTo(Rt::class);
    }

    public function posyanduAssignments(): HasMany
    {
        return $this->hasMany(PosyanduStaffAssignment::class);
    }

    private function hasValidRegionAssignment(): bool
    {
        return match ($this->role) {
            UserRole::RW => $this->position === null && $this->rw_id !== null && $this->rt_id === null,
            UserRole::RT => $this->position === null && $this->rw_id !== null
                && $this->rt_id !== null
                && Rt::query()
                    ->whereKey($this->rt_id)
                    ->where('rw_id', $this->rw_id)
                    ->exists(),
            UserRole::ADMIN => $this->position === VillagePosition::SYSTEM_ADMIN && $this->rw_id === null && $this->rt_id === null,
            UserRole::KELURAHAN => $this->position !== null && $this->rw_id === null && $this->rt_id === null,
        };
    }

    public function isVillageOffice(): bool
    {
        return in_array($this->role, [UserRole::ADMIN, UserRole::KELURAHAN], true) && $this->position !== null;
    }

    public function isSystemAdmin(): bool
    {
        return $this->is_active && $this->position === VillagePosition::SYSTEM_ADMIN;
    }

    public function isVillageHead(): bool
    {
        return $this->is_active && $this->position === VillagePosition::VILLAGE_HEAD;
    }

    public function isVillageSecretary(): bool
    {
        return $this->is_active && $this->position === VillagePosition::VILLAGE_SECRETARY;
    }
}
