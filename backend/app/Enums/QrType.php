<?php

namespace App\Enums;

enum QrType: string
{
    case DOCUMENT = 'document';
    case DECISION_SK = 'decision_sk';
    case RECEIPT = 'receipt';
    case HANDOVER = 'handover';
    case REALIZATION_ITEM = 'realization_item';
    case REALIZATION_PACKAGE = 'realization_package';
    case PROPOSAL = 'proposal';
    case GRANT_PROGRAM = 'grant_program';
    case LPJ = 'lpj';
    case FIELD_SURVEY = 'field_survey';
    case MONITORING = 'monitoring';

    public function label(): string
    {
        return match ($this) {
            self::DOCUMENT => 'Dokumen Resmi',
            self::DECISION_SK => 'Surat Keputusan (SK)',
            self::RECEIPT => 'Kuitansi / Tanda Terima',
            self::HANDOVER => 'Berita Acara Serah Terima (BAST)',
            self::REALIZATION_ITEM => 'Barang Realisasi Fisik',
            self::REALIZATION_PACKAGE => 'Paket Realisasi',
            self::PROPOSAL => 'Proposal Hibah',
            self::GRANT_PROGRAM => 'Program Hibah',
            self::LPJ => 'Laporan Pertanggungjawaban (LPJ)',
            self::FIELD_SURVEY => 'Survei Lapangan',
            self::MONITORING => 'Pemantauan Berkala',
        };
    }
}

