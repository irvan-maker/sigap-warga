<?php

namespace App\Services;

use App\Enums\ReportDispositionStatus;
use App\Enums\ReportHandlingLevel;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\Report;
use App\Models\ReportDisposition;
use App\Models\Rt;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final class ReportWorkflowService
{
    public function forward(
        Report $report,
        User $actor,
        ReportHandlingLevel $targetLevel,
        string $reason,
        ?Rt $targetRt = null,
        ?string $publicNote = null,
    ): ReportDisposition {
        return DB::transaction(function () use ($report, $actor, $targetLevel, $reason, $targetRt, $publicNote): ReportDisposition {
            $locked = Report::query()->lockForUpdate()->findOrFail($report->getKey());
            $this->assertCanManage($locked, $actor);

            if (! in_array($locked->status, [ReportStatus::NEW, ReportStatus::PROCESSING], true)) {
                throw new DomainException('Hanya laporan baru atau yang sedang diproses yang dapat diteruskan.');
            }

            [$targetRtId, $targetRwId] = $this->target($locked, $actor, $targetLevel, $targetRt);
            $oldStatus = $locked->status;
            $fromLevel = $locked->current_handling_level;

            $disposition = $locked->dispositions()->create([
                'forwarded_by_user_id' => $actor->getKey(),
                'from_level' => $fromLevel,
                'from_rt_id' => $fromLevel === ReportHandlingLevel::RT ? $locked->current_rt_id : null,
                'from_rw_id' => $locked->current_rw_id,
                'to_level' => $targetLevel,
                'to_rt_id' => $targetRtId,
                'to_rw_id' => $targetRwId,
                'reason' => trim($reason),
                'status' => ReportDispositionStatus::PENDING,
            ]);

            $locked->update([
                'status' => ReportStatus::FORWARDED,
                'current_handling_level' => $targetLevel,
                'current_rt_id' => $targetRtId,
                'current_rw_id' => $targetRwId,
                'assigned_user_id' => null,
                'acknowledged_at' => null,
            ]);
            $locked->histories()->create([
                'user_id' => $actor->getKey(),
                'old_status' => $oldStatus,
                'new_status' => ReportStatus::FORWARDED,
                'note' => sprintf(
                    'Diteruskan dari %s ke %s. Alasan: %s',
                    $fromLevel->label(),
                    $targetLevel->label(),
                    trim($reason),
                ),
                'public_note' => filled($publicNote)
                    ? trim((string) $publicNote)
                    : 'Laporan telah diverifikasi dan diteruskan kepada tingkat penanganan berikutnya.',
            ]);

            return $disposition;
        }, 3);
    }

    public function acknowledge(Report $report, User $actor): Report
    {
        return DB::transaction(function () use ($report, $actor): Report {
            $locked = Report::query()->lockForUpdate()->findOrFail($report->getKey());
            $this->assertCanManage($locked, $actor);

            $disposition = $locked->dispositions()
                ->where('status', ReportDispositionStatus::PENDING)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($disposition === null || $locked->status !== ReportStatus::FORWARDED) {
                throw new DomainException('Tidak ada disposisi yang menunggu penerimaan.');
            }

            $now = now();
            $disposition->update([
                'status' => ReportDispositionStatus::ACKNOWLEDGED,
                'acknowledged_by_user_id' => $actor->getKey(),
                'acknowledged_at' => $now,
            ]);
            $locked->update([
                'status' => ReportStatus::PROCESSING,
                'assigned_user_id' => $actor->getKey(),
                'acknowledged_at' => $now,
            ]);
            $locked->histories()->create([
                'user_id' => $actor->getKey(),
                'old_status' => ReportStatus::FORWARDED,
                'new_status' => ReportStatus::PROCESSING,
                'note' => 'Disposisi diterima oleh '.$actor->name.'.',
                'public_note' => 'Laporan telah diterima petugas dan sedang ditindaklanjuti.',
            ]);

            return $locked;
        }, 3);
    }

    private function assertCanManage(Report $report, User $actor): void
    {
        $allowed = $actor->is_active && match ($actor->role) {
            UserRole::RT => $report->current_handling_level === ReportHandlingLevel::RT
                && $report->current_rt_id === $actor->rt_id,
            UserRole::RW => $report->current_handling_level === ReportHandlingLevel::RW
                && $report->current_rw_id === $actor->rw_id,
            UserRole::KELURAHAN => $report->current_handling_level === ReportHandlingLevel::KELURAHAN,
            UserRole::ADMIN => false,
        };

        if (! $allowed) {
            throw new DomainException('Laporan ini bukan tanggung jawab aktif pengguna.');
        }
    }

    /** @return array{int|null, int|null} */
    private function target(
        Report $report,
        User $actor,
        ReportHandlingLevel $targetLevel,
        ?Rt $targetRt,
    ): array {
        if ($actor->role === UserRole::RT && $targetLevel === ReportHandlingLevel::RW) {
            return [null, $actor->rw_id];
        }

        if ($actor->role === UserRole::RW && $targetLevel === ReportHandlingLevel::KELURAHAN) {
            return [null, null];
        }

        if ($actor->role === UserRole::RW && $targetLevel === ReportHandlingLevel::RT) {
            if ($targetRt === null || $targetRt->rw_id !== $actor->rw_id || ! $targetRt->is_active) {
                throw new DomainException('RT tujuan harus aktif dan berada dalam RW yang sama.');
            }

            return [$targetRt->getKey(), $actor->rw_id];
        }

        throw new DomainException('Tujuan disposisi tidak sesuai dengan hierarki wilayah.');
    }
}
