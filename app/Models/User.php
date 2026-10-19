<?php

namespace App\Models;

use App\Enums\RoleEnum;
use App\Enums\StatusEnum;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property string $id
 * @property string $nis
 * @property string|null $kode
 * @property string $password
 * @property RoleEnum $role
 * @property StatusEnum $status
 * @property CarbonImmutable|null $voting_started_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    protected $fillable = [
        'nis',
        'kode',
        'password',
        'role',
        'status',
        'voting_started_at',
    ];

    protected $hidden = [
        'kode',
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => RoleEnum::class,
            'status' => StatusEnum::class,
            'voting_started_at' => 'immutable_datetime',
        ];
    }

    /**
     * Compare a plain 6-digit kode against the stored kode.
     */
    public function verifyKode(string $plain): bool
    {
        return $this->kode !== null
            && hash_equals($this->kode, $plain);
    }

    /**
     * Batas waktu voting: waktu mulai + durasi (menit) dari pengaturan branding.
     */
    public function votingDeadline(): ?CarbonImmutable
    {
        if ($this->voting_started_at === null) {
            return null;
        }

        $minutes = (int) config('branding.voting_duration', 3);

        return $this->voting_started_at->addMinutes($minutes);
    }

    /**
     * Sisa detik sebelum waktu voting habis; null bila timer belum dimulai.
     */
    public function votingRemainingSeconds(): ?int
    {
        $deadline = $this->votingDeadline();

        if ($deadline === null) {
            return null;
        }

        return max(0, now()->diffInSeconds($deadline, false));
    }

    /**
     * Apakah waktu voting pemilih sudah habis.
     */
    public function hasVotingExpired(): bool
    {
        $deadline = $this->votingDeadline();

        return $deadline !== null && now()->greaterThanOrEqualTo($deadline);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function candidate(): HasOne
    {
        return $this->hasOne(Candidate::class);
    }

    public function vote(): HasOne
    {
        return $this->hasOne(Vote::class);
    }

    public function voteLogs(): HasMany
    {
        return $this->hasMany(VoteLog::class);
    }
}
