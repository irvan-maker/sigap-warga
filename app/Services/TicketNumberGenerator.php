<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use OverflowException;

class TicketNumberGenerator
{
    public function generate(): string
    {
        $year = (int) now()->format('Y');

        $sequence = DB::transaction(function () use ($year): int {
            DB::table('report_ticket_sequences')->insertOrIgnore([
                'year' => $year,
                'last_number' => 0,
            ]);

            $current = DB::table('report_ticket_sequences')
                ->where('year', $year)
                ->lockForUpdate()
                ->value('last_number');

            $next = ((int) $current) + 1;

            if ($next > 99999) {
                throw new OverflowException("The report ticket sequence for {$year} is exhausted.");
            }

            DB::table('report_ticket_sequences')
                ->where('year', $year)
                ->update(['last_number' => $next]);

            return $next;
        }, 3);

        return sprintf('SGW-%d-%05d', $year, $sequence);
    }
}
