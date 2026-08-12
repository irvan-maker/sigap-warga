<?php

namespace App\Services;

use App\Context\IssuedOpaqueToken;
use App\Enums\ServiceHandoffChannel;
use App\Models\ServiceEntryPoint;
use App\Models\ServiceHandoff;
use DomainException;

final class ServiceHandoffIssuer
{
    public const TTL_MINUTES = 15;

    public function __construct(private readonly OpaqueToken $tokens) {}

    public function issue(ServiceEntryPoint $entryPoint): IssuedOpaqueToken
    {
        $entryPoint->loadMissing('rt');

        if (! $entryPoint->exists || ! $entryPoint->isAvailable()) {
            throw new DomainException('The service entry point is unavailable.');
        }

        $token = $this->tokens->issue(OpaqueToken::HANDOFF_PREFIX);
        $handoff = ServiceHandoff::query()->create([
            'token_hash' => $this->tokens->hash($token),
            'service_entry_point_id' => $entryPoint->getKey(),
            'channel' => ServiceHandoffChannel::WHATSAPP,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        return new IssuedOpaqueToken($token, $handoff);
    }
}
