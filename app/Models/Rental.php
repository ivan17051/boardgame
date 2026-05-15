<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Rental extends Model
{
    protected $table = 'rental';

    public $timestamps = false;

    protected $fillable = [
        'id_meja',
        'nama_customer',
        'waktu_start',
        'waktu_end',
        'total_durasi',
        'harga',
        'total_harga',
        'status',
    ];

    protected $casts = [
        'waktu_start' => 'datetime',
        'waktu_end' => 'datetime',
        'total_durasi' => 'decimal:2',
        'harga' => 'decimal:3',
        'total_harga' => 'decimal:3',
    ];

    public function meja(): BelongsTo
    {
        return $this->belongsTo(Meja::class, 'id_meja');
    }

    public function cashFlow(): HasOne
    {
        return $this->hasOne(CashFlow::class, 'id_rental');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
