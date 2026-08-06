<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable(['family_number', 'rt_id', 'head_citizen_id', 'address', 'is_active'])]
class FamilyCard extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (FamilyCard $card): void {
            if ($card->exists && $card->isDirty('rt_id')) {
                throw new LogicException('Kartu Keluarga tidak dapat dipindahkan ke RT lain.');
            }
            if ($card->head_citizen_id !== null && (! $card->exists || ! Citizen::query()->whereKey($card->head_citizen_id)->where('rt_id', $card->rt_id)->where('family_card_id', $card->id)->exists())) {
                throw new LogicException('Kepala keluarga harus merupakan anggota Kartu Keluarga pada RT yang sama.');
            }
        });
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function rt(): BelongsTo
    {
        return $this->belongsTo(Rt::class);
    }

    public function headCitizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class, 'head_citizen_id');
    }

    public function citizens(): HasMany
    {
        return $this->hasMany(Citizen::class);
    }
}
