<?php

namespace App\Services;

use App\Models\Citizen;
use App\Models\Report;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class CreateManualReportService
{
    public function __construct(
        private readonly CreateReportRecordService $reportRecordService,
    ) {}

    /**
     * @param array{
     *     rt_id: int,
     *     citizen_name: string,
     *     phone: string,
     *     phone_normalized: string,
     *     title: string,
     *     description: string,
     *     photos?: list<UploadedFile>
     * } $data
     */
    public function create(array $data): Report
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($data, &$storedPaths): Report {
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

                $report = $this->reportRecordService->create(
                    citizen: $citizen,
                    serviceTerritory: $citizen->rt,
                    title: $data['title'],
                    description: $data['description'],
                    reportedAt: now(),
                );

                foreach ($data['photos'] ?? [] as $photo) {
                    $extension = strtolower($photo->extension());
                    $storedName = Str::uuid()->toString().'.'.$extension;
                    $path = $photo->storeAs("reports/{$report->id}", $storedName, 'public');

                    if ($path === false) {
                        throw new RuntimeException('Failed to store report attachment.');
                    }

                    $storedPaths[] = $path;
                    $report->attachments()->create([
                        'original_name' => Str::limit(
                            basename($photo->getClientOriginalName()),
                            255,
                            '',
                        ),
                        'stored_name' => $storedName,
                        'path' => $path,
                        'mime_type' => $photo->getMimeType() ?: 'application/octet-stream',
                        'size' => $photo->getSize(),
                    ]);
                }

                return $report;
            }, 3);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);

            throw $exception;
        }
    }
}
