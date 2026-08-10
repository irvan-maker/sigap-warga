<?php

namespace App\Services;

use App\Context\IntentResolution;
use App\Enums\CitizenIntent;
use App\Enums\UrgencyLevel;
use App\Models\Rt;
use Illuminate\Support\Str;

/**
 * Conservative first-pass Indonesian intent rules without external services.
 */
class RuleBasedIntentResolver
{
    public function resolve(string $message, ?Rt $incidentRt = null): IntentResolution
    {
        $message = $this->normalize($message);

        [$intent, $urgency] = match (true) {
            $this->containsAny($message, $this->emergencyPhrases()) => [CitizenIntent::EMERGENCY, UrgencyLevel::EMERGENCY],
            $this->containsAny($message, $this->informationPhrases()) => [CitizenIntent::INFORMATION, UrgencyLevel::NORMAL],
            $this->containsAny($message, $this->letterPhrases()) => [CitizenIntent::LETTER, UrgencyLevel::NORMAL],
            $this->containsAny($message, $this->aspirationPhrases()) => [CitizenIntent::ASPIRATION, UrgencyLevel::NORMAL],
            $this->containsAny($message, $this->highPriorityReportPhrases()) => [CitizenIntent::REPORT, UrgencyLevel::HIGH],
            $this->containsAny($message, $this->normalReportPhrases()) => [CitizenIntent::REPORT, UrgencyLevel::NORMAL],
            default => [CitizenIntent::UNKNOWN, UrgencyLevel::NORMAL],
        };

        return new IntentResolution(
            intent: $intent,
            urgency: $urgency,
            incidentRt: $incidentRt,
        );
    }

    private function normalize(string $message): string
    {
        $message = Str::lower(trim($message));
        $message = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $message) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $message) ?? '');
    }

    /**
     * @param  list<string>  $phrases
     */
    private function containsAny(string $message, array $phrases): bool
    {
        foreach ($phrases as $phrase) {
            if (str_contains($message, $phrase)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    private function emergencyPhrases(): array
    {
        return [
            'tolong ambulans',
            'tolong panggil ambulans',
            'panggil ambulans sekarang',
            'butuh ambulans',
            'butuh ambulance',
            'orang pingsan',
            'orang tidak sadar',
            'kebakaran',
            'rumah terbakar',
            'kecelakaan parah',
            'kecelakaan berat',
            'pertolongan medis segera',
            'orang sesak napas',
            'ancaman keselamatan',
        ];
    }

    /** @return list<string> */
    private function informationPhrases(): array
    {
        return [
            'jam pelayanan',
            'syarat surat',
            'syarat minta ambulans',
            'kantor desa buka',
            'kantor kelurahan buka',
            'jadwal ambulans',
            'jadwal posyandu',
            'nomor ambulans',
            'nomor kontak',
            'kontak kelurahan',
            'buka jam berapa',
        ];
    }

    /** @return list<string> */
    private function letterPhrases(): array
    {
        return [
            'bikin surat domisili',
            'buat surat domisili',
            'mau bikin surat',
            'butuh surat pengantar',
            'surat pengantar',
            'urus surat usaha',
            'surat usaha',
            'surat keterangan',
            'pengantar nikah',
        ];
    }

    /** @return list<string> */
    private function aspirationPhrases(): array
    {
        return [
            'saya usul',
            'saya punya usulan',
            'usul dibuatkan',
            'usul lampu jalan',
            'saran untuk',
            'saran taman desa',
            'sebaiknya ada',
            'usulan untuk musyawarah',
        ];
    }

    /** @return list<string> */
    private function highPriorityReportPhrases(): array
    {
        return [
            'pohon tumbang menutup jalan',
            'jalan longsor',
            'banjir mulai masuk rumah',
            'banjir masuk rumah',
            'tiang listrik hampir roboh',
        ];
    }

    /** @return list<string> */
    private function normalReportPhrases(): array
    {
        return [
            'jalan depan rumah rusak',
            'jalan rusak',
            'lampu jalan mati',
            'sampah menumpuk',
            'drainase mampet',
            'saluran air tersumbat',
            'fasilitas umum rusak',
        ];
    }
}
