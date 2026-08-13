<?php

namespace App\Enums;

enum LetterType: string
{
    case GENERAL_INTRODUCTION = 'GENERAL_INTRODUCTION';
    case RW_INTRODUCTION = 'RW_INTRODUCTION';
    case DOMICILE_CERTIFICATE = 'DOMICILE_CERTIFICATE';
    case LOW_INCOME_CERTIFICATE = 'LOW_INCOME_CERTIFICATE';
    case KTP_INTRODUCTION = 'KTP_INTRODUCTION';
    case SKCK_INTRODUCTION = 'SKCK_INTRODUCTION';
    case BPJS_HEALTH_INTRODUCTION = 'BPJS_HEALTH_INTRODUCTION';

    public function label(): string
    {
        return match ($this) {
            self::GENERAL_INTRODUCTION => 'Surat Pengantar Lingkungan RT',
            self::RW_INTRODUCTION => 'Surat Pengantar Lingkungan RW',
            self::DOMICILE_CERTIFICATE => 'Surat Keterangan Domisili',
            self::LOW_INCOME_CERTIFICATE => 'Surat Keterangan Tidak Mampu',
            self::KTP_INTRODUCTION => 'Surat Pengantar Administrasi KTP',
            self::SKCK_INTRODUCTION => 'Surat Pengantar Administrasi SKCK',
            self::BPJS_HEALTH_INTRODUCTION => 'Surat Pengantar Administrasi BPJS Kesehatan',
        };
    }

    public function code(): string
    {
        return match ($this) {
            self::GENERAL_INTRODUCTION => 'SP-RT',
            self::RW_INTRODUCTION => 'SP-RW',
            self::DOMICILE_CERTIFICATE => 'SK-DOM',
            self::LOW_INCOME_CERTIFICATE => 'SK-TM',
            self::KTP_INTRODUCTION => 'SP-KTP',
            self::SKCK_INTRODUCTION => 'SP-SKCK',
            self::BPJS_HEALTH_INTRODUCTION => 'SP-BPJS',
        };
    }

    public function requiredApprovalLevel(): LetterApprovalLevel
    {
        return match ($this) {
            self::GENERAL_INTRODUCTION => LetterApprovalLevel::RT,
            self::RW_INTRODUCTION => LetterApprovalLevel::RW,
            self::DOMICILE_CERTIFICATE,
            self::LOW_INCOME_CERTIFICATE,
            self::KTP_INTRODUCTION,
            self::SKCK_INTRODUCTION,
            self::BPJS_HEALTH_INTRODUCTION => LetterApprovalLevel::KELURAHAN,
        };
    }
}
