<?php

namespace App\Services;

use App\Context\IssuedOpaqueToken;
use App\Models\Rt;
use App\Models\ServiceEntryPoint;
use DomainException;

final class ServiceEntryPointIssuer
{
    public function __construct(private readonly OpaqueToken $tokens) {}

    public function issue(Rt $rt, ?string $label = null): IssuedOpaqueToken
    {
        if (! $rt->exists || ! Rt::query()->whereKey($rt->getKey())->where('is_active', true)->exists()) {
            throw new DomainException('An active persisted RT is required.');
        }

        $token = $this->tokens->issue(OpaqueToken::ENTRY_PREFIX);
        $entryPoint = ServiceEntryPoint::query()->create([
            'token_hash' => $this->tokens->hash($token),
            'rt_id' => $rt->getKey(),
            'label' => $label === null ? null : trim($label),
        ]);

        return new IssuedOpaqueToken($token, $entryPoint);
    }
}
