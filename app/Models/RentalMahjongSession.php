<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalMahjongSession extends Model
{
    protected $table = 'rental_mahjong_session';

    protected $fillable = [
        'rental_id',
        'id_meja',
        'status',
        'access_token',
        'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class, 'rental_id');
    }

    public function meja(): BelongsTo
    {
        return $this->belongsTo(Meja::class, 'id_meja');
    }

    public function players(): HasMany
    {
        return $this->hasMany(RentalMahjongPlayer::class, 'session_id')->orderBy('seat');
    }

    public function hands(): HasMany
    {
        return $this->hasMany(RentalMahjongHand::class, 'session_id')->orderBy('hand_no');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}
