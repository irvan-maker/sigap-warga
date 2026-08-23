<?php

namespace App\Enums;

enum LetterRequirementEvidenceType: string
{
    case UNCONFIGURED = 'UNCONFIGURED';
    case MASTER_DATA = 'MASTER_DATA';
    case DOCUMENT_UPLOAD = 'DOCUMENT_UPLOAD';

    public function label(): string
    {
        return match ($this) {
            self::UNCONFIGURED => 'Belum dikonfigurasi',
            self::MASTER_DATA => 'Data master warga/keluarga',
            self::DOCUMENT_UPLOAD => 'Dokumen unggahan',
        };
    }
}
