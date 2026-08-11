<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'ticket_number',
    'citizen_id',
    'rt_id',
    'title',
    'description',
    'status',
    'reported_at',
])]
class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => ReportStatus::NEW->value,
    ];

    /** @var list<string> */
    private array $attachmentPathsPendingDeletion = [];

    protected static function booted(): void
    {
        static::creating(function (Report $report): void {
            $report->status = ReportStatus::NEW;
        });

        static::created(function (Report $report): void {
            $report->histories()->create([
                'old_status' => null,
                'new_status' => ReportStatus::NEW,
            ]);
        });

        static::deleting(function (Report $report): void {
            $report->attachmentPathsPendingDeletion = $report->attachments()
                ->pluck('path')
                ->all();
        });

        static::deleted(function (Report $report): void {
            $disk = Storage::disk('public');

            $disk->delete($report->attachmentPathsPendingDeletion);
            $disk->deleteDirectory("reports/{$report->getKey()}");
        });
    }

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'status' => ReportStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Citizen, $this>
     */
    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    /**
     * @return BelongsTo<Rt, $this>
     */
    public function rt(): BelongsTo
    {
        // Operational service/incident territory; citizen.rt is domicile.
        return $this->belongsTo(Rt::class);
    }

    /**
     * @return HasMany<ReportHistory, $this>
     */
    public function histories(): HasMany
    {
        return $this->hasMany(ReportHistory::class);
    }

    /**
     * @return HasMany<ReportAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ReportAttachment::class);
    }
}
