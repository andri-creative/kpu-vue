<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $user_nis
 * @property string|null $user_name
 * @property int $candidate_id
 * @property string $action
 * @property string|null $detail
 * @property string|null $ip_address
 * @property Carbon|null $created_at
 * @property string $tahun
 * @property Carbon $archived_at
 */
class VoteLogsArchived extends Model
{
    protected $table = 'vote_logs_archived';

    protected $fillable = [
        'user_nis',
        'user_name',
        'candidate_id',
        'action',
        'detail',
        'ip_address',
        'created_at',
        'tahun',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }
}
