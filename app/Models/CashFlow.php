<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashFlow extends Model
{
    public const KATEGORI_SEWA_MEJA = 'sewa_meja';

    public const KATEGORI_ADDITIONAL_FB = 'additional_fb';

    protected $table = 'cash_flow';

    public $timestamps = false;

    protected $fillable = [
        'id_rental',
        'tipe_transaksi',
        'kategori_pendapatan',
        'metode_pembayaran',
        'total',
        'jumlah_bayar',
        'keterangan',
        'waktu_pembayaran',
        'bukti_transaksi',
        'idc',
        'idm',
        'doc',
        'dom',
    ];

    protected $casts = [
        'total' => 'decimal:3',
        'jumlah_bayar' => 'decimal:3',
        'waktu_pembayaran' => 'datetime',
        'doc' => 'datetime',
        'dom' => 'datetime',
    ];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class, 'id_rental');
    }

    public function isIncome(): bool
    {
        return $this->tipe_transaksi == 'income';
    }

    public static function kategoriPendapatanLabel(?string $code): string
    {
        switch ($code) {
            case self::KATEGORI_SEWA_MEJA:
                return 'Sewa Meja';
            case self::KATEGORI_ADDITIONAL_FB:
                return 'Additional Item (F&B)';
            default:
                return $code ? $code : '—';
        }
    }

    public static function metodePembayaranLabel(?string $code): string
    {
        switch ($code) {
            case 'tunai':
                return 'Tunai';
            case 'transfer':
                return 'Transfer bank';
            case 'qris':
                return 'QRIS / e-wallet';
            case 'kartu':
                return 'Kartu debit/kredit';
            case 'lainnya':
                return 'Lainnya';
            default:
                return $code ? $code : '—';
        }
    }

    public function buktiUrl(): ?string
    {
        if ($this->id_rental && $this->rental && $this->rental->bukti_transaksi) {
            return $this->rental->buktiUrl();
        }

        if (! $this->bukti_transaksi) {
            return null;
        }

        return route('cashflow.bukti', $this);
    }

    public function scopeIncompleteKelengkapan(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNotNull('id_rental')
                ->whereHas('rental', function ($r) {
                    $r->incompleteKelengkapan();
                })
                ->orWhere(function ($q2) {
                    $q2->whereNull('id_rental')
                        ->where(function ($q3) {
                            $q3->whereNull('metode_pembayaran')
                                ->orWhere(function ($q4) {
                                    $q4->where('metode_pembayaran', '!=', 'tunai')
                                        ->whereNull('bukti_transaksi');
                                });
                        });
                });
        });
    }

    protected function paymentRental(): ?Rental
    {
        if (! $this->id_rental) {
            return null;
        }

        return $this->relationLoaded('rental') ? $this->rental : $this->rental()->first();
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
        $rental = $this->paymentRental();
        if ($rental) {
            return $rental->kelengkapanStatus();
        }

        $hasMetode = ! empty($this->metode_pembayaran);
        $hasBukti = ! empty($this->bukti_transaksi);
        $buktiRequired = $this->requiresBuktiTransaksi();

        if ($hasMetode && (! $buktiRequired || $hasBukti)) {
            return 'lengkap';
        }

        if ($hasMetode || $hasBukti) {
            return 'sebagian';
        }

        return 'belum';
    }

    public function amountPaid(): float
    {
        $rental = $this->paymentRental();
        if ($rental) {
            return $rental->amountPaid();
        }

        return (float) ($this->jumlah_bayar ?? $this->total);
    }

    public function billAmount(): float
    {
        $rental = $this->paymentRental();
        if ($rental) {
            return $rental->billTotal();
        }

        return (float) $this->total;
    }

    public function paymentMetode(): ?string
    {
        $rental = $this->paymentRental();

        return $rental ? $rental->metode_pembayaran : $this->metode_pembayaran;
    }

    public function paymentJumlahBayar(): ?float
    {
        $rental = $this->paymentRental();

        if ($rental) {
            return $rental->jumlah_bayar !== null ? (float) $rental->jumlah_bayar : null;
        }

        return $this->jumlah_bayar !== null ? (float) $this->jumlah_bayar : null;
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
}

