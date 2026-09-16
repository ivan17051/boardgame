<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalMahjongHandScore extends Model
{
    protected $table = 'rental_mahjong_hand_score';

    public $timestamps = false;

    protected $fillable = [
        'hand_id',
        'player_id',
        'poin',
    ];

    protected $casts = [
        'poin' => 'integer',
    ];

    public function hand(): BelongsTo
    {
        return $this->belongsTo(RentalMahjongHand::class, 'hand_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(RentalMahjongPlayer::class, 'player_id');
    }
}
