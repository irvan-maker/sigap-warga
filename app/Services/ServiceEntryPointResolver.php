<?php

namespace App\Services;

use App\Models\ServiceEntryPoint;

final class ServiceEntryPointResolver
{
    public function __construct(private readonly OpaqueToken $tokens) {}

    public function resolve(string $token): ?ServiceEntryPoint
    {
        if (! $this->tokens->isEntryToken($token)) {
            return null;
        }

        $entryPoint = ServiceEntryPoint::query()
            ->with('rt.rw')
            ->where('token_hash', $this->tokens->hash($token))
            ->first();

        return $entryPoint?->isAvailable() === true ? $entryPoint : null;
    }
}
