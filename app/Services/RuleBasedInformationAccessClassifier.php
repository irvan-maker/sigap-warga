<?php

namespace App\Services;

use App\Context\InformationAccessClassification;
use App\Enums\CapabilityRequirement;
use App\Enums\InformationAccessLevel;
use App\Enums\InformationCategory;
use App\Enums\InformationClassificationReason;
use Illuminate\Support\Str;

/**
 * Conservatively classifies information access without retrieving any data.
 */
final class RuleBasedInformationAccessClassifier
{
    public function classify(string $message): InformationAccessClassification
    {
        $message = $this->normalize($message);

        foreach ($this->protectedRules() as [$category, $phrases]) {
            if ($this->containsAny($message, $phrases)) {
                return $this->protected($category, InformationClassificationReason::PROTECTED_RULE_MATCHED);
            }
        }

        foreach ($this->publicRules() as [$category, $phrases]) {
            if ($this->containsAny($message, $phrases)) {
                return new InformationAccessClassification(
                    accessLevel: InformationAccessLevel::PUBLIC,
                    category: $category,
                    identityRequirement: CapabilityRequirement::OPTIONAL,
                    reason: InformationClassificationReason::PUBLIC_RULE_MATCHED,
                );
            }
        }

        return $this->protected(
            InformationCategory::UNKNOWN_PROTECTED,
            InformationClassificationReason::DEFAULT_PROTECTED,
        );
    }

    private function normalize(string $message): string
    {
        $message = Str::lower(trim($message));
        $message = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $message) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $message) ?? '');
    }

    /** @param list<string> $phrases */
    private function containsAny(string $message, array $phrases): bool
    {
        foreach ($phrases as $phrase) {
            if (str_contains($message, $phrase)) {
                return true;
            }
        }

        return false;
    }

    private function protected(
        InformationCategory $category,
        InformationClassificationReason $reason,
    ): InformationAccessClassification {
        return new InformationAccessClassification(
            accessLevel: InformationAccessLevel::PROTECTED,
            category: $category,
            identityRequirement: CapabilityRequirement::REQUIRED,
            reason: $reason,
        );
    }

    /** @return list<array{InformationCategory, list<string>}> */
    private function protectedRules(): array
    {
        return [
            [InformationCategory::CITIZEN_DATA, ['nik ', 'data pribadi', 'data warga bernama']],
            [InformationCategory::FAMILY_DATA, ['anggota kk', 'anggota keluarga', 'data kk']],
            [InformationCategory::PERSONAL_REPORT_STATUS, ['status laporan', 'laporan budi statusnya', 'laporan andi statusnya', 'laporan saya statusnya']],
            [InformationCategory::PERSONAL_LETTER_STATUS, ['status surat', 'surat milik', 'surat saya sudah selesai']],
            [InformationCategory::CENSUS_DATA, ['data sensus', 'sensus individual']],
            [InformationCategory::INTERNAL_ADMINISTRATION, ['informasi internal', 'data internal perangkat']],
        ];
    }

    /** @return list<array{InformationCategory, list<string>}> */
    private function publicRules(): array
    {
        return [
            [InformationCategory::SERVICE_HOURS, ['jam pelayanan', 'kantor desa buka', 'kantor kelurahan buka', 'buka jam berapa']],
            [InformationCategory::PUBLIC_CONTACT, ['nomor ambulans', 'nomor kontak', 'kontak kelurahan', 'alamat kantor']],
            [InformationCategory::PUBLIC_SCHEDULE, ['jadwal posyandu', 'jadwal pelayanan', 'jadwal ambulans']],
            [InformationCategory::SERVICE_REQUIREMENTS, ['syarat surat', 'syarat administrasi', 'syarat minta ambulans']],
            [InformationCategory::SERVICE_PROCEDURE, ['bagaimana cara mengurus', 'prosedur pelayanan', 'cara mengurus surat']],
            [InformationCategory::OFFICIAL_FEE, ['biaya resmi', 'berapa biaya', 'tarif resmi']],
        ];
    }
}
