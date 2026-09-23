<?php

namespace App\Support;

use App\Models\Meja;
use App\Models\Rental;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RentalCheckin
{
    /**
     * Start an active walk-in rental. Caller must lock the meja and run inside a transaction.
     *
     * @param  array{nama_customer:string, tipe_customer:string, id_promo?:int|null}  $input
     */
    public static function start(Meja $meja, array $input): Rental
    {
        $now = now();
        $rate = RentalCheckout::rateForMeja($meja, $input['tipe_customer']);
        $guestToken = Str::random(48);

        $created = Rental::query()->create(array_merge([
            'id_meja' => $meja->id,
            'nama_customer' => $input['nama_customer'],
            'tipe_customer' => $input['tipe_customer'],
            'waktu_start' => $now,
            'waktu_end' => null,
            'total_durasi' => null,
            'harga' => $rate,
            'total_harga' => null,
            'total_harga_sewa' => null,
            'total_harga_additional' => 0,
            'status' => 'active',
            'guest_token' => $guestToken,
        ], self::promoFields($meja, isset($input['id_promo']) ? (int) $input['id_promo'] : null, $now)));

        $meja->update(['status' => 'rented']);

        return $created;
    }

    /**
     * @return array<string, mixed>
     */
    public static function promoFields(Meja $meja, ?int $idPromo, CarbonInterface $at): array
    {
        $empty = [
            'id_promo' => null,
            'promo_nama' => null,
            'promo_hourly_rate' => null,
            'promo_duration_limit' => null,
            'promo_jam_mulai' => null,
            'promo_jam_selesai' => null,
            'promo_tgl_awal' => null,
            'promo_tgl_akhir' => null,
        ];

        if (! $idPromo) {
            return $empty;
        }

        $snapshot = RentalCheckout::resolvePromoSnapshot($idPromo, (int) $meja->id_toko, $at, false);
        if (! $snapshot) {
            throw ValidationException::withMessages([
                'id_promo' => ['Promo tidak valid, tidak aktif untuk tanggal ini, atau tidak berlaku untuk toko meja ini.'],
            ]);
        }

        return [
            'id_promo' => $snapshot['id_promo'],
            'promo_nama' => $snapshot['promo_nama'],
            'promo_hourly_rate' => $snapshot['promo_hourly_rate'],
            'promo_duration_limit' => $snapshot['promo_duration_limit'],
            'promo_jam_mulai' => $snapshot['promo_jam_mulai'],
            'promo_jam_selesai' => $snapshot['promo_jam_selesai'],
            'promo_tgl_awal' => $snapshot['promo_tgl_awal'] ?: null,
            'promo_tgl_akhir' => $snapshot['promo_tgl_akhir'] ?: null,
        ];
    }
}
