<?php

namespace App\Services;

use App\Context\TrustedInboundEvent;
use App\Context\TrustedInboundProcessingResult;
use App\Enums\CitizenIntent;
use App\Enums\ServiceEligibilityReason;
use App\Enums\TrustedInboundProcessingOutcome;

final class WhatsAppReplyFactory
{
    public function make(
        TrustedInboundEvent $event,
        TrustedInboundProcessingResult $result,
    ): ?string {
        if (in_array($result->outcome, [
            TrustedInboundProcessingOutcome::DUPLICATE_ALREADY_PROCESSED,
            TrustedInboundProcessingOutcome::PROCESSING_IN_PROGRESS,
        ], true)) {
            return null;
        }

        if ($this->isStartMessage($event->message)) {
            return $this->welcomeReply($result);
        }

        if ($result->understanding?->ruleBasedResolution->resolution->intent === CitizenIntent::EMERGENCY) {
            $contact = trim((string) config('village.emergency_contact'));
            $contactText = $contact === ''
                ? 'Segera hubungi kanal darurat resmi yang berlaku di wilayah Anda.'
                : "Segera hubungi kanal darurat resmi: {$contact}.";

            return "Pesan Anda terindikasi sebagai keadaan darurat. {$contactText}\n\n"
                .'SIGAP WARGA tahap pilot belum menggantikan layanan darurat dan tidak dapat menjanjikan bantuan telah dikirim.';
        }

        if ($result->outcome === TrustedInboundProcessingOutcome::REPORT_CREATED && $result->report !== null) {
            $reply = "Laporan Anda berhasil dicatat.\n"
                ."Nomor tiket: {$result->report->ticket_number}\n"
                ."Kategori: {$result->report->category->label()}\n"
                ."Prioritas: {$result->report->priority->label()}\n";

            if ($event->entryRt !== null && $event->entryRt->getKey() !== $result->report->rt_id) {
                $reply .= "\nQR yang dipindai berbeda dari RT domisili. Laporan tetap diterima melalui RT domisili dan dapat diteruskan sesuai hierarki wilayah.\n";
            }

            return $reply."\nSimpan nomor tiket untuk melacak perkembangan laporan.";
        }

        $eligibilityReason = $result->eligibilityDecision?->reason;

        if (in_array($eligibilityReason, [
            ServiceEligibilityReason::IDENTITY_REQUIRED,
            ServiceEligibilityReason::IDENTITY_AND_TERRITORY_REQUIRED,
        ], true)) {
            return 'Nomor WhatsApp ini belum terhubung dengan data warga aktif. '
                .'Silakan hubungi petugas RT agar identitas dapat diverifikasi. Data warga tidak dibuat otomatis dari WhatsApp.';
        }

        if (in_array($eligibilityReason, [
            ServiceEligibilityReason::TERRITORY_REQUIRED,
            ServiceEligibilityReason::IDENTITY_AND_TERRITORY_REQUIRED,
        ], true)) {
            return 'Baik, laporan Anda sudah kami terima. Untuk meneruskan laporan ke petugas yang tepat, mohon kirim RT dan RW lokasi kejadian.';
        }

        if ($result->outcome === TrustedInboundProcessingOutcome::NON_REPORT_SERVICE) {
            return 'Kebutuhan Anda sudah dikenali, tetapi modul tersebut masih berstatus prototype. '
                .'Untuk tahap pilot, silakan gunakan layanan laporan cepat atau hubungi petugas wilayah.';
        }

        if ($result->outcome === TrustedInboundProcessingOutcome::FAILED) {
            return 'Maaf, laporan belum dapat diproses. Silakan coba kembali atau hubungi petugas RT.';
        }

        return 'Saya belum dapat memahami kebutuhan Anda. Silakan jelaskan kejadian, lokasi, dan dampaknya. '
            .'Contoh: “jalan di depan Pos RT rusak dan sulit dilewati”.';
    }

    private function isStartMessage(string $message): bool
    {
        return in_array(mb_strtoupper(trim($message)), [
            'MULAI',
            'MULAI LAPOR',
            'MULAI LAPOR CEPAT',
            'MULAI LAPORAN SIGAP WARGA',
        ], true);
    }

    private function welcomeReply(TrustedInboundProcessingResult $result): string
    {
        $context = $result->understanding?->serviceUnderstanding->contextResult->context;
        $entryRt = $context?->entryRt;
        $territory = $entryRt === null
            ? 'Belum dapat diverifikasi'
            : "{$entryRt->code} / {$entryRt->rw->code}";
        $villageName = trim((string) config('village.name'));

        $reply = "✅ Anda terhubung dengan layanan resmi SIGAP WARGA.\n\n"
            ."Wilayah pintu masuk:\n{$territory}\n";

        if ($villageName !== '') {
            $reply .= "Kelurahan/Desa: {$villageName}\n";
        }

        $reply .= "\nLayanan laporan tidak dipungut biaya.\n"
            ."Kami tidak pernah meminta OTP, PIN, password, atau transfer uang.\n\n";

        if ($entryRt === null) {
            return $reply.'Konteks QR belum dapat diverifikasi. Silakan pindai QR resmi RT Anda dan mulai kembali dari portal warga.';
        }

        if ($context?->citizen === null) {
            return $reply."Nomor WhatsApp ini belum terhubung dengan data warga aktif, tetapi Anda tetap dapat membuat laporan sebagai tamu.\n"
                ."Silakan tuliskan laporan dan lokasi kejadiannya.\n"
                .'Apa yang bisa saya bantu?';
        }

        if ($context->hasTerritoryConflict()) {
            return $reply."Domisili terdaftar: {$context->identityRt?->code} / {$context->identityRt?->rw->code}.\n"
                ."QR yang dipindai berbeda dari domisili. Laporan tetap dapat diterima melalui RT domisili dan diteruskan sesuai hierarki.\n"
                .'Silakan tuliskan kejadian dan lokasi selengkap mungkin.';
        }

        return $reply."Silakan tuliskan laporan dan lokasi kejadiannya.\nApa yang bisa saya bantu?";
    }
}
