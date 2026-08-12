<?php

namespace App\Services;

use App\Enums\ServiceHandoffChannel;
use App\Models\InboundRequest;
use App\Models\Rt;
use App\Models\ServiceHandoff;
use Illuminate\Support\Facades\DB;

final class ServiceHandoffConsumer
{
    public function __construct(private readonly OpaqueToken $tokens) {}

    public function consume(string $token, InboundRequest $inboundRequest): ?Rt
    {
        if (! $inboundRequest->exists || ! $this->tokens->isHandoffToken($token)) {
            return null;
        }

        return DB::transaction(function () use ($token, $inboundRequest): ?Rt {
            $handoff = ServiceHandoff::query()
                ->with('entryPoint.rt')
                ->where('token_hash', $this->tokens->hash($token))
                ->lockForUpdate()
                ->first();

            if ($handoff === null || $handoff->channel !== ServiceHandoffChannel::WHATSAPP) {
                return null;
            }

            if ($handoff->consumed_by_inbound_request_id !== null) {
                return $handoff->consumed_by_inbound_request_id === $inboundRequest->getKey()
                    ? $handoff->entryPoint?->rt
                    : null;
            }

            if ($handoff->expires_at->isPast() || $handoff->entryPoint?->isAvailable() !== true) {
                return null;
            }

            $consumed = ServiceHandoff::query()
                ->whereKey($handoff->getKey())
                ->whereNull('consumed_by_inbound_request_id')
                ->update([
                    'consumed_at' => now(),
                    'consumed_by_inbound_request_id' => $inboundRequest->getKey(),
                    'updated_at' => now(),
                ]);

            if ($consumed !== 1) {
                $current = ServiceHandoff::query()->find($handoff->getKey());

                return $current?->consumed_by_inbound_request_id === $inboundRequest->getKey()
                    ? $handoff->entryPoint->rt
                    : null;
            }

            return $handoff->entryPoint->rt;
        }, 3);
    }
}
