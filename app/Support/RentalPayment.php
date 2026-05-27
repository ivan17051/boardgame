<?php

namespace App\Support;

use App\Models\Rental;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class RentalPayment
{
    public static function saveOnRental(
        Rental $rental,
        string $metodePembayaran,
        float $jumlahBayar,
        ?UploadedFile $bukti = null,
        ?CarbonInterface $waktuPembayaran = null
    ): Rental {
        $buktiPath = self::storeBukti($bukti, $rental->bukti_transaksi);

        $rental->update([
            'metode_pembayaran' => $metodePembayaran,
            'total' => (float) ($rental->total_harga ?? $rental->total ?? 0),
            'jumlah_bayar' => $jumlahBayar,
            'bukti_transaksi' => $buktiPath ?? $rental->bukti_transaksi,
            'waktu_pembayaran' => $waktuPembayaran ?? $rental->waktu_pembayaran ?? now(),
        ]);

        self::syncCashFlowWaktuFromRental($rental);

        return $rental->fresh();
    }

    public static function syncCashFlowWaktuFromRental(Rental $rental): void
    {
        if (! $rental->waktu_pembayaran) {
            return;
        }

        $rental->cashFlows()
            ->where('tipe_transaksi', 'income')
            ->update(['waktu_pembayaran' => $rental->waktu_pembayaran]);
    }

    public static function storeBukti(?UploadedFile $bukti, ?string $existingPath = null): ?string
    {
        if (! $bukti) {
            return $existingPath;
        }

        $disk = Storage::disk('public');
        if ($existingPath && $disk->exists($existingPath)) {
            $disk->delete($existingPath);
        }

        return $bukti->store('rental-bukti', 'public');
    }

    public static function requiresBukti(string $metode): bool
    {
        return $metode !== 'tunai';
    }
}
