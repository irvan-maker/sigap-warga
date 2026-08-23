<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'source',
    'participant_hash',
    'entry_rt_id',
    'citizen_id',
    'service_hint',
    'state',
    'last_interaction_at',
    'expires_at',
])]
class WhatsAppConversation extends Model
{
    protected $table = 'whatsapp_conversations';

    protected $hidden = ['participant_hash'];

    protected function casts(): array
    {
        return [
            'last_interaction_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }

    public function entryRt(): BelongsTo
    {
        return $this->belongsTo(Rt::class, 'entry_rt_id');
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }
}
