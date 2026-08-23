<?php

namespace App\Models;

use App\Enums\ReportCategory;
use App\Enums\ReportHandlingLevel;
use App\Enums\ReportPriority;
use App\Enums\ReportStatus;
use App\Jobs\SendRtNewReportNotification;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'ticket_number',
    'citizen_id',
    'rt_id',
    'title',
    'description',
    'status',
    'reported_at',
    'inbound_request_id',
    'entry_rt_id',
    'incident_rt_id',
    'category',
    'priority',
    'current_handling_level',
    'current_rt_id',
    'current_rw_id',
    'assigned_user_id',
    'acknowledged_at',
    'response_due_at',
    'resolution_due_at',
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
        'category' => ReportCategory::OTHER->value,
        'priority' => ReportPriority::NORMAL->value,
        'current_handling_level' => ReportHandlingLevel::RT->value,
    ];

    /** @var list<string> */
    private array $attachmentPathsPendingDeletion = [];

    protected static function booted(): void
    {
        static::creating(function (Report $report): void {
            $report->status = ReportStatus::NEW;

            if ($report->rt_id !== null) {
                $report->incident_rt_id ??= $report->rt_id;
                $report->current_rt_id ??= $report->rt_id;
                $report->current_rw_id ??= Rt::query()->whereKey($report->rt_id)->value('rw_id');
                $report->current_handling_level ??= ReportHandlingLevel::RT;
            }
        });

        static::created(function (Report $report): void {
            $report->histories()->create([
                'old_status' => null,
                'new_status' => ReportStatus::NEW,
                'public_note' => 'Laporan diterima dan menunggu pemeriksaan petugas.',
            ]);

            DB::afterCommit(fn () => SendRtNewReportNotification::dispatch($report->getKey()));
        });

        static::deleting(function (Report $report): void {
            $report->attachmentPathsPendingDeletion = $report->attachments()
                ->get(['disk', 'path'])
                ->map(fn (ReportAttachment $attachment): string => $attachment->disk.'|'.$attachment->path)
                ->all();
        });

        static::deleted(function (Report $report): void {
            foreach ($report->attachmentPathsPendingDeletion as $attachment) {
                [$disk, $path] = explode('|', $attachment, 2);
                Storage::disk($disk)->delete($path);
            }

            Storage::disk('local')->deleteDirectory("reports/{$report->getKey()}");
            Storage::disk('public')->deleteDirectory("reports/{$report->getKey()}");
        });
    }

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'status' => ReportStatus::class,
            'category' => ReportCategory::class,
            'priority' => ReportPriority::class,
            'current_handling_level' => ReportHandlingLevel::class,
            'acknowledged_at' => 'immutable_datetime',
            'response_due_at' => 'immutable_datetime',
            'resolution_due_at' => 'immutable_datetime',
        ];
    }

    public function scopeVisibleToRt(Builder $query, int $rtId): Builder
    {
        return $query->where(function (Builder $query) use ($rtId): void {
            $query
                ->where('rt_id', $rtId)
                ->orWhere('incident_rt_id', $rtId)
                ->orWhere('current_rt_id', $rtId)
                ->orWhereHas('dispositions', fn (Builder $dispositions): Builder => $dispositions
                    ->where('from_rt_id', $rtId)
                    ->orWhere('to_rt_id', $rtId));
        });
    }

    public function scopeVisibleToRw(Builder $query, int $rwId): Builder
    {
        return $query->where(function (Builder $query) use ($rwId): void {
            $query
                ->where('current_rw_id', $rwId)
                ->orWhereHas('rt', fn (Builder $rt): Builder => $rt->where('rw_id', $rwId))
                ->orWhereHas('incidentRt', fn (Builder $rt): Builder => $rt->where('rw_id', $rwId))
                ->orWhereHas('dispositions', fn (Builder $dispositions): Builder => $dispositions
                    ->where('from_rw_id', $rwId)
                    ->orWhere('to_rw_id', $rwId));
        });
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

    public function entryRt(): BelongsTo
    {
        return $this->belongsTo(Rt::class, 'entry_rt_id');
    }

    public function incidentRt(): BelongsTo
    {
        return $this->belongsTo(Rt::class, 'incident_rt_id');
    }

    public function currentRt(): BelongsTo
    {
        return $this->belongsTo(Rt::class, 'current_rt_id');
    }

    public function currentRw(): BelongsTo
    {
        return $this->belongsTo(Rw::class, 'current_rw_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * @return BelongsTo<InboundRequest, $this>
     */
    public function inboundRequest(): BelongsTo
    {
        return $this->belongsTo(InboundRequest::class);
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

    public function dispositions(): HasMany
    {
        return $this->hasMany(ReportDisposition::class);
    }
}
