<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

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
    /** @use HasFactory<\Database\Factories\ReportFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => ReportStatus::NEW->value,
    ];

    protected static function booted(): void
    {
        static::creating(function (Report $report): void {
            $report->status = ReportStatus::NEW;
        });

        static::saving(function (Report $report): void {
            $citizenBelongsToRt = Citizen::query()
                ->whereKey($report->citizen_id)
                ->where('rt_id', $report->rt_id)
                ->exists();

            if (! $citizenBelongsToRt) {
                throw new LogicException('The citizen and report must belong to the same RT.');
            }
        });

        static::created(function (Report $report): void {
            $report->histories()->create([
                'old_status' => null,
                'new_status' => ReportStatus::NEW,
            ]);
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
        return $this->belongsTo(Rt::class);
    }

    /**
     * @return HasMany<ReportHistory, $this>
     */
    public function histories(): HasMany
    {
        return $this->hasMany(ReportHistory::class);
    }
}
