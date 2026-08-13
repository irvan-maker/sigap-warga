<?php

namespace App\Models;

use App\Enums\ReportDispositionStatus;
use App\Enums\ReportHandlingLevel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'report_id',
    'forwarded_by_user_id',
    'from_level',
    'from_rt_id',
    'from_rw_id',
    'to_level',
    'to_rt_id',
    'to_rw_id',
    'reason',
    'status',
    'acknowledged_by_user_id',
    'acknowledged_at',
])]
class ReportDisposition extends Model
{
    protected function casts(): array
    {
        return [
            'from_level' => ReportHandlingLevel::class,
            'to_level' => ReportHandlingLevel::class,
            'status' => ReportDispositionStatus::class,
            'acknowledged_at' => 'immutable_datetime',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function forwardedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'forwarded_by_user_id');
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by_user_id');
    }

    public function fromRt(): BelongsTo
    {
        return $this->belongsTo(Rt::class, 'from_rt_id');
    }

    public function fromRw(): BelongsTo
    {
        return $this->belongsTo(Rw::class, 'from_rw_id');
    }

    public function toRt(): BelongsTo
    {
        return $this->belongsTo(Rt::class, 'to_rt_id');
    }

    public function toRw(): BelongsTo
    {
        return $this->belongsTo(Rw::class, 'to_rw_id');
    }
}
