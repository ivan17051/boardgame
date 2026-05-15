<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashFlow extends Model
{
    protected $table = 'cash_flow';

    public $timestamps = false;

    protected $fillable = [
        'id_rental',
        'tipe_transaksi',
        'metode_pembayaran',
        'total',
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
        if (! $this->bukti_transaksi) {
            return null;
        }

        return route('cashflow.bukti', $this);
    }

    /**
     * @return string belum|sebagian|lengkap
     */
    public function kelengkapanStatus(): string
    {
        $hasMetode = ! empty($this->metode_pembayaran);
        $hasBukti = ! empty($this->bukti_transaksi);

        if ($hasMetode && $hasBukti) {
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
}

