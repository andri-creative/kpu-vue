<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $user_id
 * @property string|null $foto
 * @property string|null $foto_url
 * @property string $nama_lengkap
 * @property int $nomor_urut
 * @property string $nis
 * @property string $kelas
 * @property string $visi
 * @property string $misi
 */
class Candidate extends Model
{
    protected $fillable = [
        'user_id',
        'foto',
        'nama_lengkap',
        'nomor_urut',
        'nis',
        'kelas',
        'visi',
        'misi',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'foto_url',
    ];

    protected function fotoUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->foto) {
                return null;
            }

            $path = rtrim((string) config('filesystems.uploads_path'), '/').'/candidates/'.$this->foto;

            return file_exists($path) ? asset('uploads/candidates/'.$this->foto) : null;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function voteLogs(): HasMany
    {
        return $this->hasMany(VoteLog::class);
    }
}
