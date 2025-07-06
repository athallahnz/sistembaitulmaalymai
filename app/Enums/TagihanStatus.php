<?php

namespace App\Enums;

enum TagihanStatus: string
{
    case LUNAS = 'lunas';
    case BELUM_LUNAS = 'belum_lunas';
    case BELUM_ADA = 'belum_ada'; // status virtual, tidak ada di DB

    public function label(): string
    {
        return match($this) {
            self::LUNAS => 'Lunas',
            self::BELUM_LUNAS => 'Belum Lunas',
            self::BELUM_ADA => 'Belum Ada Tagihan',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::LUNAS => 'bg-success',
            self::BELUM_LUNAS => 'bg-warning text-dark',
            self::BELUM_ADA => 'bg-secondary',
        };
    }
    public static function fromTagihan(float $totalTagihan, float $totalBayar): self
    {
        if ($totalTagihan === 0) {
            return self::BELUM_ADA;
        }

        if ($totalBayar >= $totalTagihan) {
            return self::LUNAS;
        }

        return self::BELUM_LUNAS;
    }
}
