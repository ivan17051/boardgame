<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MejaBooking extends Model
{
    public const STATUS_BOOKED = 'booked';

    public const STATUS_CHECKED_IN = 'checked_in';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_NO_SHOW = 'no_show';

    public const WARN_MINUTES = 30;

    public const BLOCK_MINUTES = 30;

    protected $table = 'meja_booking';

    protected $fillable = [
        'id_meja',
        'nama_customer',
        'no_hp',
        'waktu_mulai',
        'waktu_selesai',
        'status',
        'id_rental',
        'created_by',
        'catatan',
    ];

    protected $casts = [
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
    ];

    public function meja(): BelongsTo
    {
        return $this->belongsTo(Meja::class, 'id_meja');
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class, 'id_rental');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeBooked(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_BOOKED);
    }

    public function isBooked(): bool
    {
        return $this->status === self::STATUS_BOOKED;
    }

    public function minutesUntilStart(?CarbonInterface $at = null): int
    {
        $at = $at ?: now();

        return (int) $at->diffInMinutes($this->waktu_mulai, false);
    }

    public function isInBookedWindow(?CarbonInterface $at = null): bool
    {
        $at = $at ?: now();

        return $this->isBooked()
            && $at->gte($this->waktu_mulai)
            && $at->lt($this->waktu_selesai);
    }

    public function isWarningSoon(?CarbonInterface $at = null): bool
    {
        $minutes = $this->minutesUntilStart($at);

        return $this->isBooked() && $minutes >= 0 && $minutes <= self::WARN_MINUTES;
    }

    public function isHardBlockedForWalkIn(?CarbonInterface $at = null): bool
    {
        if (! $this->isBooked()) {
            return false;
        }

        if ($this->isInBookedWindow($at)) {
            return true;
        }

        $minutes = $this->minutesUntilStart($at);

        return $minutes >= 0 && $minutes <= self::WARN_MINUTES;
    }

    /**
     * @return array<string, mixed>
     */
    public function warningPayload(): array
    {
        return [
            'id' => (int) $this->id,
            'id_meja' => (int) $this->id_meja,
            'nama_meja' => optional($this->meja)->nama,
            'nama_customer' => $this->nama_customer,
            'no_hp' => $this->no_hp,
            'waktu_mulai' => $this->waktu_mulai ? $this->waktu_mulai->toIso8601String() : null,
            'waktu_mulai_label' => $this->waktu_mulai ? $this->waktu_mulai->format('d/m H:i') : null,
            'waktu_selesai_label' => $this->waktu_selesai ? $this->waktu_selesai->format('H:i') : null,
            'minutes_until' => $this->minutesUntilStart(),
            'is_warning' => $this->isWarningSoon(),
            'is_blocked' => $this->isHardBlockedForWalkIn(),
        ];
    }
}
