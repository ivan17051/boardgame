<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Meja extends Model
{
    use HasFactory;

    protected $table = 'm_meja';

    public $timestamps = false;

    protected $fillable = [
        'id_toko',
        'nama',
        'harga',
        'status',
        'idc',
        'doc',
        'idm',
        'dom',
    ];

    protected $casts = [
        'harga' => 'decimal:2',
        'doc' => 'datetime',
        'dom' => 'datetime',
    ];

    public function toko(): BelongsTo
    {
        return $this->belongsTo(Toko::class, 'id_toko');
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class, 'id_meja');
    }

    public function activeRental(): HasOne
    {
        return $this->hasOne(Rental::class, 'id_meja')->where('status', 'active');
    }
}
