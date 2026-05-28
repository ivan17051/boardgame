<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdditionalItem extends Model
{
    protected $table = 'm_additional_item';

    public $timestamps = false;

    protected $fillable = [
        'id_toko',
        'nama',
        'harga',
        'is_active',
        'idc',
        'idm',
        'doc',
        'dom',
    ];

    protected $casts = [
        'id_toko' => 'integer',
        'harga' => 'decimal:3',
        'is_active' => 'boolean',
        'doc' => 'datetime',
        'dom' => 'datetime',
    ];

    public function toko(): BelongsTo
    {
        return $this->belongsTo(Toko::class, 'id_toko');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
