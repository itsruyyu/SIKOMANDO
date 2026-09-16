<?php

namespace App\Enums;

enum ProposalStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case VERIFICATION = 'verification';
    case REVISION = 'revision';
    case VERIFIED = 'verified';
    case EVALUATION = 'evaluation';
    case SURVEY = 'survey';
    case RECOMMENDED = 'recommended';
    case APPROVAL = 'approval';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case DISBURSED = 'disbursed';
    case IMPLEMENTATION = 'implementation';
    case LPJ_SUBMITTED = 'lpj_submitted';
    case LPJ_VERIFIED = 'lpj_verified';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Diajukan',
            self::VERIFICATION => 'Verifikasi',
            self::REVISION => 'Revisi',
            self::VERIFIED => 'Terverifikasi',
            self::EVALUATION => 'Evaluasi',
            self::SURVEY => 'Survei Lapangan',
            self::RECOMMENDED => 'Direkomendasikan',
            self::APPROVAL => 'Persetujuan',
            self::APPROVED => 'Disetujui',
            self::REJECTED => 'Ditolak',
            self::DISBURSED => 'Dana Disalurkan',
            self::IMPLEMENTATION => 'Pelaksanaan',
            self::LPJ_SUBMITTED => 'LPJ Diajukan',
            self::LPJ_VERIFIED => 'LPJ Diverifikasi',
            self::COMPLETED => 'Selesai',
            self::CANCELLED => 'Dibatalkan',
        };
    }
}
