<?php

namespace App\Context;

use App\Enums\UserRole;
use App\Enums\VillagePosition;

/**
 * Caller-supplied staff scope facts; this grants no permissions.
 */
final readonly class StaffScopeContext
{
    public function __construct(
        public UserRole $role,
        public ?VillagePosition $position = null,
        public ?int $rwId = null,
        public ?int $rtId = null,
    ) {}
}
