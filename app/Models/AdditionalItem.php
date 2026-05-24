<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdditionalItem extends Model
{
    protected $table = 'm_additional_item';

    public $timestamps = false;

    protected $fillable = [
        'nama',
        'harga',
        'is_active',
        'idc',
        'idm',
        'doc',
        'dom',
    ];

    protected $casts = [
        'harga' => 'decimal:3',
        'is_active' => 'boolean',
        'doc' => 'datetime',
        'dom' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
