<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

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
        'id_promo',
        'promo_nama',
        'promo_hourly_rate',
        'promo_duration_limit',
        'promo_jam_mulai',
        'promo_jam_selesai',
        'promo_tgl_awal',
        'promo_tgl_akhir',
        'total_harga',
        'total_harga_sewa',
        'total_harga_additional',
        'status',
        'guest_token',
        'metode_pembayaran',
        'total',
        'jumlah_bayar',
        'bukti_transaksi',
        'waktu_pembayaran',
    ];

    protected $casts = [
        'waktu_start' => 'datetime',
        'waktu_end' => 'datetime',
        'waktu_pembayaran' => 'datetime',
        'total_durasi' => 'decimal:2',
        'harga' => 'decimal:3',
        'promo_hourly_rate' => 'decimal:3',
        'promo_duration_limit' => 'decimal:2',
        'promo_tgl_awal' => 'date',
        'promo_tgl_akhir' => 'date',
        'total_harga' => 'decimal:3',
        'total_harga_sewa' => 'decimal:3',
        'total_harga_additional' => 'decimal:3',
        'total' => 'decimal:3',
        'jumlah_bayar' => 'decimal:3',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function (self $rental) {
            $rental->deleteDependents();
        });
    }

    public function deleteDependents(): void
    {
        $disk = Storage::disk('public');

        CashFlow::query()
            ->where('id_rental', $this->id)
            ->get(['id', 'bukti_transaksi'])
            ->each(function (CashFlow $flow) use ($disk) {
                if ($flow->bukti_transaksi && $disk->exists($flow->bukti_transaksi)) {
                    $disk->delete($flow->bukti_transaksi);
                }
            });

        CashFlow::query()->where('id_rental', $this->id)->delete();
        RentalAdditionalItem::query()->where('id_rental', $this->id)->delete();

        if ($this->bukti_transaksi && $disk->exists($this->bukti_transaksi)) {
            $disk->delete($this->bukti_transaksi);
        }
    }

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

    public function hasPromo(): bool
    {
        return $this->promo_hourly_rate !== null;
    }

    public function hasPromoDurationLimit(): bool
    {
        return $this->promo_duration_limit !== null
            && (float) $this->promo_duration_limit > 0;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function billTotal(): float
    {
        return (float) ($this->total ?? $this->total_harga ?? 0);
    }

    public function amountPaid(): float
    {
        return (float) ($this->jumlah_bayar ?? $this->billTotal());
    }

    public function requiresBuktiTransaksi(): bool
    {
        return $this->metode_pembayaran !== 'tunai';
    }

    /**
     * @return string belum|sebagian|lengkap
     */
    public function kelengkapanStatus(): string
    {
        $flows = $this->relationLoaded('cashFlows')
            ? $this->cashFlows
            : $this->cashFlows()->get();

        if ($flows->isNotEmpty()) {
            $statuses = $flows->map(function (CashFlow $flow) {
                return $flow->kelengkapanStatus();
            });

            if ($statuses->every(function ($s) {
                return $s === 'lengkap';
            })) {
                return 'lengkap';
            }

            if ($statuses->contains('lengkap') || $statuses->contains('sebagian')) {
                return 'sebagian';
            }

            // All cashflows unpaid — fall through to rental-level fields (legacy)
        }

        $hasMetode = ! empty($this->metode_pembayaran);
        $hasBukti = ! empty($this->bukti_transaksi);
        $buktiRequired = $hasMetode && $this->requiresBuktiTransaksi();

        if ($hasMetode && (! $buktiRequired || $hasBukti)) {
            return 'lengkap';
        }

        if ($hasMetode || $hasBukti) {
            return 'sebagian';
        }

        return 'belum';
    }

    public function kelengkapanStatusLabel(): string
    {
        switch ($this->kelengkapanStatus()) {
            case 'lengkap':
                return 'Lengkap';
            case 'sebagian':
                return 'Sebagian';
            default:
                return 'Belum lengkap';
        }
    }

    public function buktiUrl(): ?string
    {
        if (! $this->bukti_transaksi) {
            return null;
        }

        return route('rental.bukti', $this);
    }

    public function scopeIncompleteKelengkapan(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('metode_pembayaran')
                ->orWhere(function ($q2) {
                    $q2->where('metode_pembayaran', '!=', 'tunai')
                        ->whereNull('bukti_transaksi');
                });
        });
    }
}
