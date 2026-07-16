<?php

namespace App\Support;

use App\Models\AppLog;
use App\Models\Rental;

class RentalAudit
{
    /** @var array<int, string> */
    private const TRACKED_FIELDS = [
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
        'total',
        'metode_pembayaran',
        'jumlah_bayar',
        'bukti_transaksi',
        'waktu_pembayaran',
        'status',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function snapshot(Rental $rental): array
    {
        $data = [];

        foreach (self::TRACKED_FIELDS as $field) {
            $value = $rental->getAttribute($field);

            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d H:i:s');
            } elseif (is_object($value) && method_exists($value, '__toString')) {
                $value = (string) $value;
            }

            $data[$field] = $value;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $original
     * @param  array<string, mixed>|null  $new
     */
    public static function logUpdate(Rental $rental, array $original, ?array $new = null): ?AppLog
    {
        $new = $new ?? self::snapshot($rental->fresh() ?? $rental);

        if (! self::hasChanges($original, $new)) {
            return null;
        }

        return AppLog::record('rental', (int) $rental->id, 'update', $original, $new);
    }

    /**
     * @param  array<string, mixed>  $original
     */
    public static function logDelete(Rental $rental, array $original): AppLog
    {
        return AppLog::record('rental', (int) $rental->id, 'delete', $original, null);
    }

    /**
     * @param  array<string, mixed>  $original
     * @param  array<string, mixed>  $new
     */
    private static function hasChanges(array $original, array $new): bool
    {
        foreach (self::TRACKED_FIELDS as $field) {
            $from = array_key_exists($field, $original) ? $original[$field] : null;
            $to = array_key_exists($field, $new) ? $new[$field] : null;

            if (is_numeric($from) && is_numeric($to)) {
                if ((string) (0 + $from) !== (string) (0 + $to)) {
                    return true;
                }
                continue;
            }

            if ((string) $from !== (string) $to) {
                return true;
            }
        }

        return false;
    }
}
