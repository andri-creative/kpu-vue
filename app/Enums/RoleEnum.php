<?php

namespace App\Enums;

enum RoleEnum: string
{
    case Admin = 'admin';
    case Pemilih = 'pemilih';
    case Kandidat = 'kandidat';

    /**
     * @return array<self>
     */
    public static function assignable(): array
    {
        return [self::Pemilih, self::Kandidat];
    }
}
