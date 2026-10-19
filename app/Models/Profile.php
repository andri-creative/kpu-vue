<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $user_id
 * @property string $nama_lengkap
 * @property string $kelas
 */
class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'nama_lengkap',
        'kelas',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
