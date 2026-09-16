<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalMahjongHand extends Model
{
    protected $table = 'rental_mahjong_hand';

    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'hand_no',
        'winner_seat',
        'voided_at',
        'created_at',
    ];

    protected $casts = [
        'hand_no' => 'integer',
        'winner_seat' => 'integer',
        'voided_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(RentalMahjongSession::class, 'session_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(RentalMahjongHandScore::class, 'hand_id');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }
}
