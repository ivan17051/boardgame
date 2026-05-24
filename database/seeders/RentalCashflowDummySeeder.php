<?php

namespace Database\Seeders;

use App\Models\CashFlow;
use App\Models\Meja;
use App\Models\Rental;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RentalCashflowDummySeeder extends Seeder
{
    private const RENTAL_COUNT = 100;

    private const CUSTOMER_NAMES = [
        'Budi Santoso', 'Ani Wijaya', 'Rina Kusuma', 'Dedi Pratama', 'Siti Rahayu',
        'Agus Hermawan', 'Maya Lestari', 'Hendra Gunawan', 'Putri Maharani', 'Rizki Aditya',
        'Wulan Sari', 'Fajar Nugroho', 'Dewi Anggraini', 'Yoga Prasetyo', 'Lina Hartono',
        'Bambang Sutrisno', 'Citra Dewi', 'Eko Wibowo', 'Gita Permata', 'Hadi Susanto',
        'Indah Puspita', 'Joko Widodo', 'Kartika Sari', 'Lukman Hakim', 'Mira Anggrek',
        'Nanda Pratama', 'Oki Setiawan', 'Pratiwi Utami', 'Qori Sandria', 'Rafi Ahmad',
        'Salsa Bintang', 'Tono Hartono', 'Umi Kalsum', 'Vina Melati', 'Wahyu Nugroho',
        'Xena Putri', 'Yuni Astuti', 'Zaki Ramadhan', 'Ahmad Fauzi', 'Belinda Rose',
    ];

    private const METODE_OPTIONS = ['tunai', 'transfer', 'qris', 'kartu', 'lainnya', null];

    public function run(): void
    {
        $mejas = Meja::query()->with('toko')->get();

        if ($mejas->isEmpty()) {
            $this->command->error('Tidak ada data meja (m_meja). Tambahkan toko & meja terlebih dahulu.');

            return;
        }

        $this->command->info('Menyisipkan '.self::RENTAL_COUNT.' baris rental dan cash_flow...');

        $created = 0;

        for ($i = 0; $i < self::RENTAL_COUNT; $i++) {
            $meja = $mejas->random();
            $hargaPerJam = (float) $meja->harga;
            $durationMinutes = random_int(15, 240);
            $daysAgo = random_int(0, 89);
            $start = now()
                ->subDays($daysAgo)
                ->subHours(random_int(8, 22))
                ->subMinutes(random_int(0, 59))
                ->startOfMinute();
            $end = $start->copy()->addMinutes($durationMinutes);
            $totalHarga = round(($durationMinutes / 60) * $hargaPerJam, 3);

            $namaCustomer = self::CUSTOMER_NAMES[array_rand(self::CUSTOMER_NAMES)]
                .' #'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);

            $rental = Rental::query()->create([
                'id_meja' => $meja->id,
                'nama_customer' => $namaCustomer,
                'waktu_start' => $start,
                'waktu_end' => $end,
                'total_durasi' => $durationMinutes,
                'harga' => $hargaPerJam,
                'total_harga' => $totalHarga,
                'status' => 'completed',
                'guest_token' => null,
            ]);

            $mejaNama = $meja->nama ?? 'Meja';
            $tokoNama = $meja->toko->nama ?? '';
            $keterangan = $tokoNama !== ''
                ? "Sewa meja {$mejaNama} ({$tokoNama}) — {$namaCustomer}"
                : "Sewa meja {$mejaNama} — {$namaCustomer}";

            $metode = $this->randomMetode();
            $jumlahBayar = $this->randomJumlahBayar($totalHarga, $metode);
            $bukti = $this->randomBukti($metode);

            CashFlow::query()->create([
                'id_rental' => $rental->id,
                'tipe_transaksi' => 'income',
                'metode_pembayaran' => $metode,
                'total' => $totalHarga,
                'jumlah_bayar' => $jumlahBayar,
                'keterangan' => $keterangan,
                'waktu_pembayaran' => $end,
                'bukti_transaksi' => $bukti,
                'idc' => 0,
                'idm' => 0,
                'doc' => $end,
                'dom' => $end,
            ]);

            $created++;
        }

        $this->command->info("Selesai: {$created} rental + {$created} cash_flow (income) ditambahkan.");
    }

    private function randomMetode(): ?string
    {
        $roll = random_int(1, 100);

        if ($roll <= 35) {
            return 'tunai';
        }
        if ($roll <= 55) {
            return 'transfer';
        }
        if ($roll <= 70) {
            return 'qris';
        }
        if ($roll <= 85) {
            return 'kartu';
        }
        if ($roll <= 92) {
            return 'lainnya';
        }

        return null;
    }

    private function randomJumlahBayar(float $total, ?string $metode): ?float
    {
        if ($metode === null) {
            return null;
        }

        $roll = random_int(1, 100);

        if ($roll <= 70 || $metode === 'tunai') {
            return $total;
        }

        if ($roll <= 90) {
            return round($total * (random_int(50, 95) / 100), 3);
        }

        return null;
    }

    private function randomBukti(?string $metode): ?string
    {
        if ($metode === null || $metode === 'tunai') {
            return null;
        }

        if (random_int(1, 100) <= 55) {
            return null;
        }

        return 'cash-flow-bukti/dummy-'.Str::lower(Str::random(24)).'.jpg';
    }
}
