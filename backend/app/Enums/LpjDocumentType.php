<?php

namespace App\Enums;

enum LpjDocumentType: string
{
    case FINANCIAL_REPORT = 'financial_report';
    case ACTIVITY_REPORT = 'activity_report';
    case RECEIPT = 'receipt';
    case PHOTO_DOCUMENTATION = 'photo_documentation';
    case BANK_STATEMENT = 'bank_statement';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::FINANCIAL_REPORT => 'Laporan Keuangan',
            self::ACTIVITY_REPORT => 'Laporan Kegiatan Fisik',
            self::RECEIPT => 'Kuitansi / Bukti Pembayaran',
            self::PHOTO_DOCUMENTATION => 'Dokumentasi Foto / Video Kegiatan',
            self::BANK_STATEMENT => 'Rekening Koran Kas Bantuan',
            self::OTHER => 'Dokumen Pendukung Lainnya',
        };
    }
}
