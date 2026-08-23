<?php

namespace App\Jobs;

use App\Models\Report;
use App\Services\WhatsAppMessageSender;
use App\Support\PhoneNumberNormalizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

final class SendRtNewReportNotification implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(public readonly int $reportId)
    {
        $this->onQueue('whatsapp');
    }

    public function handle(
        WhatsAppMessageSender $sender,
        PhoneNumberNormalizer $phoneNumberNormalizer,
    ): void {
        $report = Report::query()
            ->with(['rt.rw'])
            ->find($this->reportId);

        if ($report === null || $report->rt === null) {
            return;
        }

        $rt = $report->rt;

        if ($rt->is_active !== true
            || $rt->report_notification_enabled !== true
            || ! is_string($rt->whatsapp_number)
            || trim($rt->whatsapp_number) === '') {
            return;
        }

        $recipient = $phoneNumberNormalizer->normalize($rt->whatsapp_number);

        if (preg_match('/^62\d{8,13}$/', $recipient) !== 1) {
            return;
        }

        $priority = match (strtoupper((string) $report->priority->value)) {
            'HIGH' => 'Tinggi',
            'NORMAL' => 'Normal',
            default => ucfirst(strtolower((string) $report->priority->value)),
        };

        $title = Str::limit(
            preg_replace('/\s+/u', ' ', trim($report->title)) ?: 'Laporan warga',
            120,
            '…',
        );

        $sender->sendTemplateOrFail(
            recipient: $recipient,
            templateName: 'sigap_laporan_baru_rt',
            languageCode: 'id',
            bodyParameters: [
                $report->ticket_number,
                $rt->code,
                $rt->rw?->code ?? '-',
                $title,
                $priority,
            ],
        );
    }
}
