<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $nis
 * @property string $role
 * @property string $status
 * @property string $tahun
 * @property Carbon $archived_at
 */
class UsersArchived extends Model
{
    use HasUuids;

    protected $table = 'users_archived';

    protected $fillable = [
        'nis',
        'role',
        'status',
        'tahun',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }
}
