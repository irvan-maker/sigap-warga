<?php

namespace App\Services;

use App\Models\VillageLetter;
use Illuminate\Support\Facades\DB;

class LetterNumberService
{
    public function issue(VillageLetter $letter): string
    {
        $year = (int) now()->format('Y');

        DB::table('letter_number_sequences')->insertOrIgnore([
            'year' => $year,
            'last_number' => 0,
        ]);

        $sequence = DB::table('letter_number_sequences')
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        $next = $sequence->last_number + 1;

        DB::table('letter_number_sequences')
            ->where('year', $year)
            ->update([
                'last_number' => $next,
            ]);

        $letterCode = $letter->isGenericSubmission()
            ? ($letter->submission?->letter_type_code ?? 'GEN')
            : $letter->letter_type->code();

        return sprintf(
            '%03d/%s/%s/%s/%d',
            $next,
            $letterCode,
            config('village.code', 'CS'),
            $this->romanMonth((int) now()->format('n')),
            $year,
        );
    }

    private function romanMonth(int $month): string
    {
        return [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ][$month];
    }
}