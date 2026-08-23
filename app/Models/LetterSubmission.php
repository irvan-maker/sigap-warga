<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable(['village_letter_id', 'applicant_phone_hash', 'letter_type_code', 'letter_type_name', 'letter_type_description', 'version_number', 'configuration_snapshot', 'submitted_at'])]
class LetterSubmission extends Model
{
    protected $hidden = [
        'applicant_phone_hash',
        'configuration_snapshot',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $submission): void {
            if ($submission->sealed_at !== null) {
                throw new LogicException('Snapshot baru harus dibuat dalam keadaan belum disegel.');
            }

            $validParent = VillageLetter::query()
                ->whereKey($submission->village_letter_id)
                ->whereNull('letter_type')
                ->whereNotNull('letter_type_id')
                ->whereNotNull('letter_type_version_id')
                ->exists();

            if (! $validParent) {
                throw new LogicException('Snapshot hanya dapat dibuat untuk pengajuan surat generik yang valid.');
            }
        });

        static::updating(function (): void {
            throw new LogicException('Snapshot pengajuan surat tidak dapat diubah.');
        });

        static::deleting(function (): void {
            throw new LogicException('Snapshot pengajuan surat tidak dapat dihapus langsung.');
        });
    }

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'configuration_snapshot' => 'array',
            'submitted_at' => 'datetime',
            'sealed_at' => 'datetime',
        ];
    }

    public function seal(): void
    {
        if (! $this->exists || $this->sealed_at !== null) {
            throw new LogicException('Snapshot pengajuan surat hanya dapat disegel satu kali.');
        }

        if ($this->isDirty()) {
            throw new LogicException('Snapshot pengajuan surat tidak dapat diubah sebelum disegel.');
        }

        $sealedAt = $this->freshTimestamp();
        $affected = self::query()
            ->whereKey($this->getKey())
            ->whereNull('sealed_at')
            ->update([
                'sealed_at' => $sealedAt,
                $this->getUpdatedAtColumn() => $sealedAt,
            ]);

        if ($affected !== 1) {
            throw new LogicException('Snapshot pengajuan surat telah disegel atau tidak dapat disegel.');
        }

        $this->refresh();
    }

    public function letter(): BelongsTo
    {
        return $this->belongsTo(VillageLetter::class, 'village_letter_id');
    }

    public function fieldValues(): HasMany
    {
        return $this->hasMany(LetterFieldValue::class)->orderBy('sequence');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(LetterRequirementSubmission::class)->orderBy('sequence');
    }
}
