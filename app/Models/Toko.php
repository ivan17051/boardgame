<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Toko extends Model
{
    use HasFactory;

    protected $table = 'm_toko';

    public $timestamps = false;

    protected $fillable = [
        'nama',
        'alamat',
        'jumlah_meja',
        'doc',
        'idm',
        'dom',
    ];

    protected $casts = [
        'doc' => 'datetime',
        'dom' => 'datetime',
        'jumlah_meja' => 'integer',
    ];

    public function meja(): HasMany
    {
        return $this->hasMany(Meja::class, 'id_toko')->orderBy('id');
    }
}
