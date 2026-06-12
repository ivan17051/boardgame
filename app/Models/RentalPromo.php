<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalPromo extends Model
{
    protected $table = 'm_rental_promo';

    public $timestamps = false;

    protected $fillable = [
        'id_toko',
        'nama',
        'promo_hourly_rate',
        'promo_duration_limit',
        'jam_mulai',
        'jam_selesai',
        'tgl_awal',
        'tgl_akhir',
        'is_active',
        'idc',
        'idm',
        'doc',
        'dom',
    ];

    protected $casts = [
        'id_toko' => 'integer',
        'promo_hourly_rate' => 'decimal:3',
        'promo_duration_limit' => 'decimal:2',
        'tgl_awal' => 'date',
        'tgl_akhir' => 'date',
        'is_active' => 'boolean',
        'doc' => 'datetime',
        'dom' => 'datetime',
    ];

    public function toko(): BelongsTo
    {
        return $this->belongsTo(Toko::class, 'id_toko');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeActiveAt(Builder $query, CarbonInterface $at): Builder
    {
        $date = $at->format('Y-m-d');
        $time = $at->format('H:i:s');

        return $query->active()
            ->where(function (Builder $q) use ($date) {
                $q->whereNull('tgl_awal')->orWhere('tgl_awal', '<=', $date);
            })
            ->where(function (Builder $q) use ($date) {
                $q->whereNull('tgl_akhir')->orWhere('tgl_akhir', '>=', $date);
            })
            ->where(function (Builder $q) use ($time) {
                $q->where(function (Builder $inner) use ($time) {
                    $inner->whereColumn('jam_mulai', '<=', 'jam_selesai')
                        ->where('jam_mulai', '<=', $time)
                        ->where('jam_selesai', '>=', $time);
                })->orWhere(function (Builder $inner) use ($time) {
                    $inner->whereColumn('jam_mulai', '>', 'jam_selesai')
                        ->where(function (Builder $overnight) use ($time) {
                            $overnight->where('jam_mulai', '<=', $time)
                                ->orWhere('jam_selesai', '>=', $time);
                        });
                });
            });
    }

    public static function normalizeTimeString(?string $time): string
    {
        if ($time === null || $time === '') {
            return '00:00:00';
        }

        $parts = explode(':', $time);
        $h = (int) ($parts[0] ?? 0);
        $m = (int) ($parts[1] ?? 0);
        $s = (int) ($parts[2] ?? 0);

        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }

    public static function normalizeDateString($date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        if ($date instanceof CarbonInterface) {
            return $date->format('Y-m-d');
        }

        return (string) $date;
    }

    public function jamMulaiFormatted(): string
    {
        return substr(self::normalizeTimeString($this->jam_mulai), 0, 5);
    }

    public function jamSelesaiFormatted(): string
    {
        return substr(self::normalizeTimeString($this->jam_selesai), 0, 5);
    }

    public function tglAwalFormatted(): string
    {
        return $this->tgl_awal ? $this->tgl_awal->format('d/m/Y') : '—';
    }

    public function tglAkhirFormatted(): string
    {
        return $this->tgl_akhir ? $this->tgl_akhir->format('d/m/Y') : '—';
    }

    public function periodeFormatted(): string
    {
        if (! $this->tgl_awal && ! $this->tgl_akhir) {
            return 'Tanpa batas';
        }

        if ($this->tgl_awal && ! $this->tgl_akhir) {
            return 'Dari '.$this->tglAwalFormatted();
        }

        if (! $this->tgl_awal && $this->tgl_akhir) {
            return 'Hingga '.$this->tglAkhirFormatted();
        }

        return $this->tglAwalFormatted().' – '.$this->tglAkhirFormatted();
    }

    public function isActiveAt(CarbonInterface $at): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $date = $at->format('Y-m-d');
        $tglAwal = self::normalizeDateString($this->tgl_awal);
        $tglAkhir = self::normalizeDateString($this->tgl_akhir);

        if ($tglAwal && $date < $tglAwal) {
            return false;
        }

        if ($tglAkhir && $date > $tglAkhir) {
            return false;
        }

        $time = $at->format('H:i:s');
        $mulai = self::normalizeTimeString($this->jam_mulai);
        $selesai = self::normalizeTimeString($this->jam_selesai);

        if ($mulai <= $selesai) {
            return $time >= $mulai && $time <= $selesai;
        }

        return $time >= $mulai || $time <= $selesai;
    }
}
