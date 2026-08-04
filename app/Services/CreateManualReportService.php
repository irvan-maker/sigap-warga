<?php

namespace App\Services;

use App\Models\Citizen;
use App\Models\Report;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateManualReportService
{
    public function __construct(
        private readonly TicketNumberGenerator $ticketNumberGenerator,
    ) {}

    /**
     * @param array{
     *     rt_id: int,
     *     citizen_name: string,
     *     phone: string,
     *     phone_normalized: string,
     *     title: string,
     *     description: string
     * } $data
     */
    public function create(array $data): Report
    {
        return DB::transaction(function () use ($data): Report {
            $citizen = Citizen::query()
                ->where('phone_normalized', $data['phone_normalized'])
                ->lockForUpdate()
                ->first();

            if ($citizen && $citizen->rt_id !== $data['rt_id']) {
                throw ValidationException::withMessages([
                    'phone' => 'Nomor telepon sudah terdaftar pada RT lain.',
                ]);
            }

            if (! $citizen) {
                $citizen = Citizen::query()->create([
                    'rt_id' => $data['rt_id'],
                    'name' => $data['citizen_name'],
                    'phone' => $data['phone'],
                    'phone_normalized' => $data['phone_normalized'],
                ]);
            } elseif ($citizen->name !== $data['citizen_name']) {
                $citizen->update(['name' => $data['citizen_name']]);
            }

            return Report::query()->create([
                'ticket_number' => $this->ticketNumberGenerator->generate(),
                'citizen_id' => $citizen->id,
                'rt_id' => $data['rt_id'],
                'title' => $data['title'],
                'description' => $data['description'],
                'reported_at' => now(),
            ]);
        }, 3);
    }
}
