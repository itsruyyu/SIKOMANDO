<?php

namespace App\Enums;

enum RealizationItemStatus: string
{
    case CREATED = 'created';
    case PURCHASED = 'purchased';
    case RECEIVED = 'received';
    case VERIFIED = 'verified';
    case ASSIGNED = 'assigned';
    case MONITORED = 'monitored';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Didaftarkan',
            self::PURCHASED => 'Dibeli / Dipesan',
            self::RECEIVED => 'Diterima di Lokasi',
            self::VERIFIED => 'Telah Diverifikasi Lapangan',
            self::ASSIGNED => 'Diserahterimakan / Digunakan',
            self::MONITORED => 'Dalam Pemantauan Berkala',
            self::CLOSED => 'Selesai Siklus',
        };
    }
}

