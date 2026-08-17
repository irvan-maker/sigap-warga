<?php

namespace App\Services;

use App\Context\IntentResolution;
use App\Context\RuleBasedIntentResolution;
use App\Enums\CitizenIntent;
use App\Enums\IntentRule;
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
        return $this->resolveWithExplanation($message, $incidentRt)->resolution;
    }

    public function resolveWithExplanation(
        string $message,
        ?Rt $incidentRt = null,
    ): RuleBasedIntentResolution {
        $message = $this->normalize($message);

        if ($match = $this->findEmergencyMatch($message)) {
            return $this->result(
                CitizenIntent::EMERGENCY,
                UrgencyLevel::EMERGENCY,
                $incidentRt,
                ...$match,
            );
        }

        $families = [
            [CitizenIntent::INFORMATION, UrgencyLevel::NORMAL, $this->informationRules()],
            [CitizenIntent::LETTER, UrgencyLevel::NORMAL, $this->letterRules()],
            [CitizenIntent::ASPIRATION, UrgencyLevel::NORMAL, $this->aspirationRules()],
            [CitizenIntent::REPORT, UrgencyLevel::HIGH, $this->highPriorityReportRules()],
            [CitizenIntent::REPORT, UrgencyLevel::NORMAL, $this->normalReportRules()],
        ];

        $hasExplicitReportIntent = $this->hasExplicitReportIntent($message);

        foreach ($families as [$intent, $urgency, $rules]) {
            if ($intent === CitizenIntent::REPORT
                && $urgency === UrgencyLevel::NORMAL
                && ! $hasExplicitReportIntent) {
                continue;
            }

            if ($match = $this->findMatch($message, $rules)) {
                return $this->result($intent, $urgency, $incidentRt, ...$match);
            }
        }

        if ($this->looksLikeHighPriorityReport($message)) {
            return $this->result(
                CitizenIntent::REPORT,
                UrgencyLevel::HIGH,
                $incidentRt,
                IntentRule::UNKNOWN_NO_MATCH,
                null,
            );
        }

        if ($hasExplicitReportIntent) {
            return $this->result(
                CitizenIntent::REPORT,
                UrgencyLevel::NORMAL,
                $incidentRt,
                IntentRule::UNKNOWN_NO_MATCH,
                null,
            );
        }

        return $this->result(
            CitizenIntent::UNKNOWN,
            UrgencyLevel::NORMAL,
            $incidentRt,
            IntentRule::UNKNOWN_NO_MATCH,
            null,
        );
    }

    private function normalize(string $message): string
    {
        $message = Str::lower(trim($message));
        $message = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $message) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $message) ?? '');
    }

    /**
     * @return array{IntentRule, string}|null
     */
    private function findEmergencyMatch(string $message): ?array
    {
        foreach ($this->emergencyRules() as [$rule, $phrase]) {
            if (! str_contains($message, $phrase)) {
                continue;
            }

            if (! $this->isUnconsciousStatePhrase($phrase)
                && $this->isNegatedNearPhrase($message, $phrase)) {
                continue;
            }

            return [$rule, $phrase];
        }

        return null;
    }

    /**
     * @param  list<array{IntentRule, string}>  $rules
     * @return array{IntentRule, string}|null
     */
    private function findMatch(string $message, array $rules): ?array
    {
        foreach ($rules as [$rule, $phrase]) {
            if (str_contains($message, $phrase)) {
                return [$rule, $phrase];
            }
        }

        if (
            in_array(IntentRule::REPORT_ROAD_DAMAGE, array_column($rules, 0), true)
            && preg_match('/\bjalan(?:\s+[\p{L}\p{N}]+){0,8}\s+(?:rusak|berlubang|retak|ambles)\b/u', $message, $matches) === 1
            && preg_match('/\b(?:tidak|bukan|nggak|gak|ga|enggak|belum)\s+(?:rusak|berlubang|retak|ambles)\b/u', $message) !== 1
        ) {
            return [IntentRule::REPORT_ROAD_DAMAGE, trim($matches[0])];
        }

        if (
            in_array(IntentRule::REPORT_STREET_LIGHT, array_column($rules, 0), true)
            && preg_match('/\b(?:lampu|pju|penerangan)(?:\s+[\p{L}\p{N}]+){0,12}\s+(?:mati|padam|rusak|tidak menyala|gak menyala|ga menyala|nggak menyala|enggak menyala)\b/u', $message, $matches) === 1
        ) {
            return [IntentRule::REPORT_STREET_LIGHT, trim($matches[0])];
        }

        return null;
    }

    private function looksLikeHighPriorityReport(string $message): bool
    {
        if (preg_match(
            '/\b(?:pemerkosaan|perkosaan|pelecehan seksual|kekerasan seksual|pencabulan|kdrt|penganiayaan|penyerangan|penusukan|ditusuk|ditikam|pembunuhan|perampokan|begal|pencurian|orang hilang)\b/u',
            $message,
        ) === 1) {
            return true;
        }

        return preg_match(
            '/\b(?:jembatan(?:\s+[\p{L}\p{N}]+){0,4}\s+(?:putus|ambruk|roboh)|(?:akses|jalan)(?:\s+[\p{L}\p{N}]+){0,4}\s+(?:tertutup|terputus|tidak bisa dilalui|gak bisa dilalui|ga bisa dilalui|nggak bisa dilalui|enggak bisa dilalui)|longsor(?:\s+[\p{L}\p{N}]+){0,5}\s+(?:menutup|menutupi)(?:\s+(?:jalan|akses))?|pohon(?:\s+[\p{L}\p{N}]+){0,4}\s+tumbang(?:\s+[\p{L}\p{N}]+){0,4}\s+(?:menutup|menutupi)(?:\s+(?:jalan|akses))?|tiang\s+listrik(?:\s+[\p{L}\p{N}]+){0,4}\s+(?:hampir\s+roboh|roboh))\b/u',
            $message,
        ) === 1;
    }

    private function hasExplicitReportIntent(string $message): bool
    {
        return preg_match(
            '/\b(?:lapor|laporan|melapor|melaporkan)\b/u',
            $message,
        ) === 1;
    }

    private function isNegatedNearPhrase(string $message, string $phrase): bool
    {
        $position = strpos($message, $phrase);

        if ($position === false) {
            return false;
        }

        $prefix = substr($message, max(0, $position - 50), min(50, $position));

        return preg_match(
            '/(?:tidak|bukan|nggak|gak|ga|enggak|belum)(?:\s+[\p{L}\p{N}]+){0,2}\s*$/u',
            $prefix,
        ) === 1;
    }

    private function isUnconsciousStatePhrase(string $phrase): bool
    {
        return in_array($phrase, [
            'tidak sadar',
            'gak sadar',
            'ga sadar',
            'nggak sadar',
            'enggak sadar',
            'belum sadar',
        ], true);
    }

    private function result(
        CitizenIntent $intent,
        UrgencyLevel $urgency,
        ?Rt $incidentRt,
        IntentRule $rule,
        ?string $phrase,
    ): RuleBasedIntentResolution {
        return new RuleBasedIntentResolution(
            resolution: new IntentResolution($intent, $urgency, $incidentRt),
            matchedRule: $rule,
            matchedCategory: $intent,
            matchedPhrase: $phrase,
        );
    }

    /** @return list<array{IntentRule, string}> */
    private function emergencyRules(): array
    {
        return [
            [IntentRule::EMERGENCY_DIRECT_AMBULANCE_REQUEST, 'tolong panggil ambulans'],
            [IntentRule::EMERGENCY_DIRECT_AMBULANCE_REQUEST, 'panggil ambulans sekarang'],
            [IntentRule::EMERGENCY_DIRECT_AMBULANCE_REQUEST, 'tolong ambulans'],
            [IntentRule::EMERGENCY_DIRECT_AMBULANCE_REQUEST, 'butuh ambulans'],
            [IntentRule::EMERGENCY_DIRECT_AMBULANCE_REQUEST, 'butuh ambulance'],
            [IntentRule::EMERGENCY_PERSON_UNCONSCIOUS, 'orang tidak sadar'],
            [IntentRule::EMERGENCY_PERSON_UNCONSCIOUS, 'orang gak sadar'],
            [IntentRule::EMERGENCY_PERSON_UNCONSCIOUS, 'orang ga sadar'],
            [IntentRule::EMERGENCY_PERSON_UNCONSCIOUS, 'orang nggak sadar'],
            [IntentRule::EMERGENCY_PERSON_UNCONSCIOUS, 'orang enggak sadar'],
            [IntentRule::EMERGENCY_PERSON_UNCONSCIOUS, 'tidak sadar'],
            [IntentRule::EMERGENCY_PERSON_UNCONSCIOUS, 'gak sadar'],
            [IntentRule::EMERGENCY_PERSON_UNCONSCIOUS, 'ga sadar'],
            [IntentRule::EMERGENCY_PERSON_UNCONSCIOUS, 'nggak sadar'],
            [IntentRule::EMERGENCY_PERSON_UNCONSCIOUS, 'enggak sadar'],
            [IntentRule::EMERGENCY_PERSON_UNCONSCIOUS, 'belum sadar'],
            [IntentRule::EMERGENCY_PERSON_UNCONSCIOUS, 'pingsan'],
            [IntentRule::EMERGENCY_FIRE, 'rumah terbakar'],
            [IntentRule::EMERGENCY_FIRE, 'rumah kebakar'],
            [IntentRule::EMERGENCY_FIRE, 'kebakaran'],
            [IntentRule::EMERGENCY_FIRE, 'kebakar'],
            [IntentRule::EMERGENCY_SEVERE_ACCIDENT, 'kecelakaan parah'],
            [IntentRule::EMERGENCY_SEVERE_ACCIDENT, 'kecelakaan berat'],
            [IntentRule::EMERGENCY_MEDICAL_HELP, 'pertolongan medis segera'],
            [IntentRule::EMERGENCY_BREATHING_DIFFICULTY, 'sesak napas'],
            [IntentRule::EMERGENCY_SAFETY_THREAT, 'ancaman keselamatan'],
        ];
    }

    /** @return list<array{IntentRule, string}> */
    private function informationRules(): array
    {
        return [
            [IntentRule::INFORMATION_AMBULANCE_REQUIREMENTS, 'syarat minta ambulans'],
            [IntentRule::INFORMATION_AMBULANCE_COST, 'berapa biaya ambulans'],
            [IntentRule::INFORMATION_AMBULANCE_CONTACT, 'nomor ambulans'],
            [IntentRule::INFORMATION_AMBULANCE_SCHEDULE, 'jadwal ambulans'],
            [IntentRule::INFORMATION_LETTER_REQUIREMENTS, 'syarat surat'],
            [IntentRule::INFORMATION_SERVICE_HOURS, 'jam pelayanan'],
            [IntentRule::INFORMATION_SERVICE_HOURS, 'kantor desa buka'],
            [IntentRule::INFORMATION_SERVICE_HOURS, 'kantor kelurahan buka'],
            [IntentRule::INFORMATION_SERVICE_HOURS, 'buka jam berapa'],
            [IntentRule::INFORMATION_POSYANDU_SCHEDULE, 'jadwal posyandu'],
            [IntentRule::INFORMATION_PUBLIC_CONTACT, 'nomor kontak'],
            [IntentRule::INFORMATION_PUBLIC_CONTACT, 'kontak kelurahan'],
            [IntentRule::INFORMATION_SERVICE_PROCEDURE, 'bagaimana cara mengurus'],
            [IntentRule::INFORMATION_SERVICE_PROCEDURE, 'prosedur pelayanan'],
            [IntentRule::INFORMATION_SERVICE_PROCEDURE, 'cara mengurus surat'],
            [IntentRule::INFORMATION_OFFICIAL_FEE, 'biaya resmi'],
            [IntentRule::INFORMATION_OFFICIAL_FEE, 'tarif resmi'],
            [IntentRule::INFORMATION_PROTECTED_REQUEST, 'nik '],
            [IntentRule::INFORMATION_PROTECTED_REQUEST, 'anggota kk'],
            [IntentRule::INFORMATION_PROTECTED_REQUEST, 'status laporan'],
            [IntentRule::INFORMATION_PROTECTED_REQUEST, 'laporan budi statusnya'],
            [IntentRule::INFORMATION_PROTECTED_REQUEST, 'laporan andi statusnya'],
            [IntentRule::INFORMATION_PROTECTED_REQUEST, 'status surat'],
            [IntentRule::INFORMATION_PROTECTED_REQUEST, 'surat milik'],
            [IntentRule::INFORMATION_PROTECTED_REQUEST, 'data sensus'],
            [IntentRule::INFORMATION_PROTECTED_REQUEST, 'informasi internal'],
            [IntentRule::INFORMATION_AMBIGUOUS_REQUEST, 'informasi tentang warga'],
        ];
    }

    /** @return list<array{IntentRule, string}> */
    private function letterRules(): array
    {
        return [
            [IntentRule::LETTER_DOMICILE, 'bikin surat domisili'],
            [IntentRule::LETTER_DOMICILE, 'buat surat domisili'],
            [IntentRule::LETTER_DOMICILE, 'buatkan surat domisili'],
            [IntentRule::LETTER_INTRODUCTION, 'butuh surat pengantar'],
            [IntentRule::LETTER_INTRODUCTION, 'surat pengantar'],
            [IntentRule::LETTER_BUSINESS, 'urus surat usaha'],
            [IntentRule::LETTER_BUSINESS, 'surat usaha'],
            [IntentRule::LETTER_CERTIFICATE, 'surat keterangan'],
            [IntentRule::LETTER_MARRIAGE_INTRODUCTION, 'pengantar nikah'],
            [IntentRule::LETTER_INTRODUCTION, 'mau bikin surat'],
        ];
    }

    /** @return list<array{IntentRule, string}> */
    private function aspirationRules(): array
    {
        return [
            [IntentRule::ASPIRATION_PROPOSAL, 'saya punya usulan'],
            [IntentRule::ASPIRATION_PROPOSAL, 'usulan untuk musyawarah'],
            [IntentRule::ASPIRATION_PROPOSAL, 'usul dibuatkan'],
            [IntentRule::ASPIRATION_PROPOSAL, 'usul lampu jalan'],
            [IntentRule::ASPIRATION_PROPOSAL, 'saya usul'],
            [IntentRule::ASPIRATION_SUGGESTION, 'saran taman desa'],
            [IntentRule::ASPIRATION_SUGGESTION, 'saran untuk'],
            [IntentRule::ASPIRATION_SUGGESTION, 'sebaiknya ada'],
        ];
    }

    /** @return list<array{IntentRule, string}> */
    private function highPriorityReportRules(): array
    {
        return [
            [IntentRule::REPORT_HIGH_FALLEN_TREE, 'pohon tumbang menutup jalan'],
            [IntentRule::REPORT_HIGH_LANDSLIDE, 'jalan longsor'],
            [IntentRule::REPORT_HIGH_FLOOD, 'banjir mulai masuk rumah'],
            [IntentRule::REPORT_HIGH_FLOOD, 'banjir masuk rumah'],
            [IntentRule::REPORT_HIGH_UTILITY_POLE, 'tiang listrik hampir roboh'],
        ];
    }

    /** @return list<array{IntentRule, string}> */
    private function normalReportRules(): array
    {
        return [
            [IntentRule::REPORT_ROAD_DAMAGE, 'jalan depan rumah rusak'],
            [IntentRule::REPORT_ROAD_DAMAGE, 'jalan rusak'],
            [IntentRule::REPORT_STREET_LIGHT, 'lampu jalan mati'],
            [IntentRule::REPORT_GARBAGE, 'sampah menumpuk'],
            [IntentRule::REPORT_DRAINAGE, 'drainase mampet'],
            [IntentRule::REPORT_DRAINAGE, 'saluran air tersumbat'],
            [IntentRule::REPORT_PUBLIC_FACILITY, 'fasilitas umum rusak'],
        ];
    }
}
