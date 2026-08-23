<?php

namespace App\Models;

use App\Enums\LetterApprovalLevel;
use App\Enums\LetterStatus;
use App\Enums\LetterType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use LogicException;

#[Fillable(['public_tracking_code', 'letter_number', 'letter_type', 'letter_type_id', 'letter_type_version_id', 'required_approval_level', 'citizen_id', 'rt_id', 'submitted_by', 'reviewed_by_rw', 'approved_by_village', 'approved_by_user_id', 'purpose', 'notes', 'status', 'submitted_at', 'reviewed_at', 'approved_at', 'issued_at', 'is_active'])]
class VillageLetter extends Model
{
    protected $attributes = [
        'status' => 'DRAFT',
        'required_approval_level' => LetterApprovalLevel::KELURAHAN->value,
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $letter): void {
            $letter->public_tracking_code ??= 'SRT-'.strtoupper(Str::random(12));

            if ($letter->letter_type_version_id !== null && $letter->letter_type_id === null) {
                throw new LogicException('Version jenis surat tidak boleh disimpan tanpa jenis surat.');
            }

            if ($letter->letter_type === null) {
                $validGenericPair = $letter->letter_type_id !== null
                    && $letter->letter_type_version_id !== null
                    && LetterTypeDefinition::query()
                        ->whereKey($letter->letter_type_id)
                        ->where('is_active', true)
                        ->whereHas('versions', fn ($query) => $query
                            ->whereKey($letter->letter_type_version_id)
                            ->where('status', 'PUBLISHED'))
                        ->exists();

                if (! $validGenericPair) {
                    throw new LogicException('Pengajuan surat generik memerlukan pasangan jenis/version published yang aktif dan konsisten.');
                }
            }
        });

        static::saving(function (self $letter): void {
            if ($letter->exists && $letter->isDirty('public_tracking_code')) {
                throw new LogicException('Nomor pengajuan publik tidak dapat diubah.');
            }
            if ($letter->exists && $letter->isDirty(['citizen_id', 'rt_id'])) {
                throw new LogicException('Warga dan wilayah surat tidak dapat dipindahkan.');
            }
            if ($letter->exists && $letter->isDirty(['letter_type_id', 'letter_type_version_id'])) {
                throw new LogicException('Pasangan jenis dan version surat tidak dapat diubah setelah pengajuan dibuat.');
            }
            if ($letter->persistedAsGenericSubmission()
                && $letter->isDirty([
                    'letter_type',
                    'letter_type_id',
                    'letter_type_version_id',
                    'submitted_by',
                    'required_approval_level',
                ])) {
                throw new LogicException('Discriminator pengajuan surat generik tidak dapat diubah.');
            }

            if (! Citizen::query()->whereKey($letter->citizen_id)->where('rt_id', $letter->rt_id)->exists()) {
                throw new LogicException('Warga dan surat harus berada pada RT yang sama.');
            }
        });

        static::deleting(function (self $letter): void {
            if ($letter->persistedAsGenericSubmission() || $letter->isGenericSubmission()) {
                throw new LogicException('Pengajuan surat generik tidak dapat dihapus langsung.');
            }
        });
    }

    protected function casts(): array
    {
        return ['letter_type' => LetterType::class, 'required_approval_level' => LetterApprovalLevel::class, 'status' => LetterStatus::class, 'submitted_at' => 'datetime', 'reviewed_at' => 'datetime', 'approved_at' => 'datetime', 'issued_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function rt(): BelongsTo
    {
        return $this->belongsTo(Rt::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_rw');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function letterTypeDefinition(): BelongsTo
    {
        return $this->belongsTo(LetterTypeDefinition::class, 'letter_type_id');
    }

    public function letterTypeVersion(): BelongsTo
    {
        return $this->belongsTo(LetterTypeVersion::class, 'letter_type_version_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(VillageLetterHistory::class);
    }

    public function submission(): HasOne
    {
        return $this->hasOne(LetterSubmission::class);
    }

    public function isGenericSubmission(): bool
    {
        return $this->letter_type === null
            && $this->letter_type_id !== null
            && $this->letter_type_version_id !== null;
    }

    public function typeLabel(): string
    {
        return $this->letter_type?->label()
            ?? $this->submission?->letter_type_name
            ?? $this->letterTypeDefinition?->name
            ?? 'Jenis surat tidak tersedia';
    }

    private function persistedAsGenericSubmission(): bool
    {
        return $this->exists
            && $this->getRawOriginal('letter_type') === null
            && $this->getRawOriginal('letter_type_id') !== null
            && $this->getRawOriginal('letter_type_version_id') !== null;
    }
}
