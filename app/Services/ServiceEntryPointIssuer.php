<?php

namespace App\Services;

use App\Context\IssuedOpaqueToken;
use App\Models\Rt;
use App\Models\ServiceEntryPoint;
use DomainException;
use Illuminate\Support\Facades\DB;

final class ServiceEntryPointIssuer
{
    public function __construct(private readonly OpaqueToken $tokens) {}

    public function issue(Rt $rt, ?string $label = null): IssuedOpaqueToken
    {
        if (! $rt->exists) {
            throw new DomainException('An active persisted RT is required.');
        }

        return DB::transaction(function () use ($rt, $label): IssuedOpaqueToken {
            $lockedRt = Rt::query()
                ->with('rw')
                ->lockForUpdate()
                ->find($rt->getKey());

            if ($lockedRt?->isAvailableForService() !== true) {
                throw new DomainException('An active persisted RT and RW are required.');
            }

            if ($lockedRt->activeServiceEntryPoints()->exists()) {
                throw new DomainException('RT ini sudah memiliki satu QR aktif. Nonaktifkan QR lama sebelum menerbitkan pengganti.');
            }

            $token = $this->tokens->issue(OpaqueToken::ENTRY_PREFIX);
            $entryPoint = ServiceEntryPoint::query()->create([
                'token_hash' => $this->tokens->hash($token),
                'rt_id' => $lockedRt->getKey(),
                'label' => $label === null ? null : trim($label),
            ]);

            return new IssuedOpaqueToken($token, $entryPoint);
        }, 3);
    }
}
