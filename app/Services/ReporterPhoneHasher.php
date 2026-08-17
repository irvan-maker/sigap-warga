<?php

namespace App\Services;

use App\Support\PhoneNumberNormalizer;
use DomainException;

final class ReporterPhoneHasher
{
    public function __construct(
        private readonly PhoneNumberNormalizer $phoneNumberNormalizer,
    ) {}

    public function hash(string $phone): string
    {
        $key = config('app.key');

        if (! is_string($key) || $key === '') {
            throw new DomainException('Application key is required for reporter privacy.');
        }

        return hash_hmac(
            'sha256',
            $this->phoneNumberNormalizer->normalize($phone),
            $key,
        );
    }
}
