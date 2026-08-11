<?php

namespace App\Models;

use App\Enums\InboundProcessingReason;
use App\Enums\InboundRequestStatus;
use App\Enums\ServiceRouteTarget;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'source',
    'external_event_id',
    'correlation_id',
    'status',
    'service_target',
    'processing_reason',
    'attempt_count',
    'received_at',
    'processing_started_at',
    'completed_at',
    'last_error_code',
])]
class InboundRequest extends Model
{
    /** @var list<string> */
    protected $hidden = [
        'external_event_id',
        'correlation_id',
        'last_error_code',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => InboundRequestStatus::RECEIVED->value,
        'attempt_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => InboundRequestStatus::class,
            'service_target' => ServiceRouteTarget::class,
            'processing_reason' => InboundProcessingReason::class,
            'attempt_count' => 'integer',
            'received_at' => 'immutable_datetime',
            'processing_started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasOne<Report, $this>
     */
    public function report(): HasOne
    {
        return $this->hasOne(Report::class);
    }
}
