<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_nis
 * @property string|null $user_name
 * @property int $candidate_id
 * @property string|null $candidate_name
 * @property Carbon $voted_at
 * @property string $tahun
 * @property Carbon $archived_at
 */
class VotesArchived extends Model
{
    use HasUuids;

    protected $table = 'votes_archived';

    protected $fillable = [
        'user_nis',
        'user_name',
        'candidate_id',
        'candidate_name',
        'voted_at',
        'tahun',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'voted_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }
}
