<?php

namespace App\Models;

use App\Enums\FamilyRelationship;
use Database\Factories\CitizenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['rt_id', 'family_card_id', 'family_relationship', 'nik', 'name', 'phone', 'phone_normalized', 'gender', 'birth_place', 'birth_date', 'address', 'is_active'])]
class Citizen extends Model
{
    /** @use HasFactory<CitizenFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Citizen $citizen): void {
            if ($citizen->exists && $citizen->isDirty('rt_id')) {
                throw new \LogicException('Warga tidak dapat dipindahkan ke RT lain.');
            }
            if ($citizen->family_card_id !== null && ! FamilyCard::query()->whereKey($citizen->family_card_id)->where('rt_id', $citizen->rt_id)->exists()) {
                throw new \LogicException('Warga dan Kartu Keluarga harus berada di RT yang sama.');
            }
            if ($citizen->family_relationship !== null && $citizen->family_card_id === null) {
                throw new \LogicException('Hubungan keluarga hanya dapat diisi untuk warga yang memiliki Kartu Keluarga.');
            }
            if ($citizen->family_relationship === FamilyRelationship::HEAD && ! FamilyCard::query()->whereKey($citizen->family_card_id)->where('head_citizen_id', $citizen->id)->exists()) {
                throw new \LogicException('Hubungan kepala keluarga harus sinkron dengan Kartu Keluarga.');
            }
            if ($citizen->exists && $citizen->getOriginal('family_card_id') !== null && $citizen->isDirty('family_card_id') && FamilyCard::query()->whereKey($citizen->getOriginal('family_card_id'))->where('head_citizen_id', $citizen->id)->exists()) {
                throw new \LogicException('Kepala keluarga harus diganti sebelum dipindahkan dari Kartu Keluarga.');
            }
        });
    }

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'is_active' => 'boolean', 'family_relationship' => FamilyRelationship::class];
    }

    /**
     * @return BelongsTo<Rt, $this>
     */
    public function rt(): BelongsTo
    {
        return $this->belongsTo(Rt::class);
    }

    public function familyCard(): BelongsTo
    {
        return $this->belongsTo(FamilyCard::class);
    }

    /**
     * @return HasMany<Report, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
