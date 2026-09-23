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
        'harga_member',
        'status',
        'idc',
        'doc',
        'idm',
        'dom',
    ];

    protected $casts = [
        'harga' => 'decimal:2',
        'harga_member' => 'decimal:3',
        'doc' => 'datetime',
        'dom' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function (self $meja) {
            Rental::query()
                ->where('id_meja', $meja->id)
                ->get()
                ->each->delete();

            MejaBooking::query()->where('id_meja', $meja->id)->delete();
        });
    }

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

    public function bookings(): HasMany
    {
        return $this->hasMany(MejaBooking::class, 'id_meja');
    }

    public function upcomingBookings(): HasMany
    {
        return $this->hasMany(MejaBooking::class, 'id_meja')
            ->where('status', MejaBooking::STATUS_BOOKED)
            ->where('waktu_selesai', '>', now())
            ->orderBy('waktu_mulai');
    }
}
