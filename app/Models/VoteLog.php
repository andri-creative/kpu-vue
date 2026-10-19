<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $user_id
 * @property int $candidate_id
 * @property string $action
 * @property string|null $detail
 * @property string|null $ip_address
 */
class VoteLog extends Model
{
    protected $fillable = [
        'user_id',
        'candidate_id',
        'action',
        'detail',
        'ip_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
