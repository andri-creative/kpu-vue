<?php

namespace App\Enums;

enum StatusEnum: string
{
    case Aktif = 'aktif';
    case SudahVoting = 'sudah_voting';
    case Kedaluwarsa = 'kedaluwarsa';
    case Nonaktif = 'nonaktif';
}
