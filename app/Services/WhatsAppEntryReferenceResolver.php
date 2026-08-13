<?php

namespace App\Services;

use App\Models\Rt;

final class WhatsAppEntryReferenceResolver
{
    public function resolve(?string $rtCode, ?string $rwCode): ?Rt
    {
        if ($rtCode === null || $rwCode === null) {
            return null;
        }

        $rt = Rt::query()
            ->with('rw')
            ->withCount('activeServiceEntryPoints')
            ->where('code', $rtCode)
            ->where('is_active', true)
            ->whereHas('rw', fn ($query) => $query
                ->where('code', $rwCode)
                ->where('is_active', true))
            ->first();

        return (int) ($rt?->active_service_entry_points_count ?? 0) >= 1
            && $rt->isAvailableForService()
                ? $rt
                : null;
    }
}
