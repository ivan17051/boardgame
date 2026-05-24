<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Rental extends Model
{
    protected $table = 'rental';

    public $timestamps = false;

    protected $fillable = [
        'id_meja',
        'nama_customer',
        'tipe_customer',
        'waktu_start',
        'waktu_end',
        'total_durasi',
        'harga',
        'total_harga',
        'total_harga_sewa',
        'total_harga_additional',
        'status',
        'guest_token',
    ];

    protected $casts = [
        'waktu_start' => 'datetime',
        'waktu_end' => 'datetime',
        'total_durasi' => 'decimal:2',
        'harga' => 'decimal:3',
        'total_harga' => 'decimal:3',
        'total_harga_sewa' => 'decimal:3',
        'total_harga_additional' => 'decimal:3',
    ];

    public function meja(): BelongsTo
    {
        return $this->belongsTo(Meja::class, 'id_meja');
    }

    public function cashFlows(): HasMany
    {
        return $this->hasMany(CashFlow::class, 'id_rental');
    }

    public function cashFlow(): HasOne
    {
        return $this->hasOne(CashFlow::class, 'id_rental');
    }

    public function additionalItems(): HasMany
    {
        return $this->hasMany(RentalAdditionalItem::class, 'id_rental');
    }

    public function isMember(): bool
    {
        return $this->tipe_customer === 'member';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
