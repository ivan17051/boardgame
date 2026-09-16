<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalMahjongPlayer extends Model
{
    protected $table = 'rental_mahjong_player';

    protected $fillable = [
        'session_id',
        'seat',
        'nama',
        'is_renter',
    ];

    protected $casts = [
        'seat' => 'integer',
        'is_renter' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(RentalMahjongSession::class, 'session_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(RentalMahjongHandScore::class, 'player_id');
    }
}
