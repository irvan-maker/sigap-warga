<?php

namespace App\Services;

final class OpaqueToken
{
    public const ENTRY_PREFIX = 'sep_';

    public const HANDOFF_PREFIX = 'swh_';

    public function issue(string $prefix): string
    {
        return $prefix.$this->base64Url(random_bytes(32));
    }

    public function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public function isEntryToken(string $token): bool
    {
        return $this->matches($token, self::ENTRY_PREFIX);
    }

    public function isHandoffToken(string $token): bool
    {
        return $this->matches($token, self::HANDOFF_PREFIX);
    }

    private function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    private function matches(string $token, string $prefix): bool
    {
        return preg_match('/\A'.preg_quote($prefix, '/').'[A-Za-z0-9_-]{43}\z/', $token) === 1;
    }
}
