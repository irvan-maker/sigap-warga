<?php

namespace App\Models;

use App\Enums\ServiceHandoffChannel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'token_hash',
    'service_entry_point_id',
    'channel',
    'expires_at',
    'consumed_at',
    'consumed_by_inbound_request_id',
])]
class ServiceHandoff extends Model
{
    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'channel' => ServiceHandoffChannel::class,
            'expires_at' => 'immutable_datetime',
            'consumed_at' => 'immutable_datetime',
        ];
    }

    public function entryPoint(): BelongsTo
    {
        return $this->belongsTo(ServiceEntryPoint::class, 'service_entry_point_id');
    }

    public function consumedByInboundRequest(): BelongsTo
    {
        return $this->belongsTo(InboundRequest::class, 'consumed_by_inbound_request_id');
    }
}
