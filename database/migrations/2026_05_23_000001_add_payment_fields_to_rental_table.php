<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental', function (Blueprint $table) {
            if (! Schema::hasColumn('rental', 'metode_pembayaran')) {
                $table->string('metode_pembayaran', 100)->nullable()->after('total_harga_additional');
            }
            if (! Schema::hasColumn('rental', 'total')) {
                $table->decimal('total', 15, 3)->nullable()->after('metode_pembayaran');
            }
            if (! Schema::hasColumn('rental', 'jumlah_bayar')) {
                $table->decimal('jumlah_bayar', 15, 3)->nullable()->after('total');
            }
            if (! Schema::hasColumn('rental', 'bukti_transaksi')) {
                $table->string('bukti_transaksi', 512)->nullable()->after('jumlah_bayar');
            }
            if (! Schema::hasColumn('rental', 'waktu_pembayaran')) {
                $table->dateTime('waktu_pembayaran')->nullable()->after('bukti_transaksi');
            }
        });

        if (Schema::hasTable('cash_flow')) {
            $rows = DB::table('cash_flow')
                ->where('tipe_transaksi', 'income')
                ->whereNotNull('id_rental')
                ->orderBy('id')
                ->get()
                ->groupBy('id_rental');

            foreach ($rows as $rentalId => $flows) {
                $first = $flows->first();
                $jumlahBayar = null;
                $bukti = null;
                $sumTotal = 0;
                foreach ($flows as $f) {
                    $sumTotal += (float) $f->total;
                    if ($jumlahBayar === null && $f->jumlah_bayar !== null) {
                        $jumlahBayar = $f->jumlah_bayar;
                    }
                    if ($bukti === null && ! empty($f->bukti_transaksi)) {
                        $bukti = $f->bukti_transaksi;
                    }
                }
                $totalHarga = DB::table('rental')->where('id', $rentalId)->value('total_harga');

                DB::table('rental')
                    ->where('id', $rentalId)
                    ->whereNull('metode_pembayaran')
                    ->update([
                        'metode_pembayaran' => $first->metode_pembayaran,
                        'total' => $totalHarga !== null ? $totalHarga : $sumTotal,
                        'jumlah_bayar' => $jumlahBayar !== null ? $jumlahBayar : $sumTotal,
                        'bukti_transaksi' => $bukti,
                        'waktu_pembayaran' => $first->waktu_pembayaran,
                    ]);
            }

            DB::table('rental')
                ->whereNull('total')
                ->whereNotNull('total_harga')
                ->update(['total' => DB::raw('total_harga')]);
        }
    }

    public function down(): void
    {
        Schema::table('rental', function (Blueprint $table) {
            foreach (['waktu_pembayaran', 'bukti_transaksi', 'jumlah_bayar', 'total', 'metode_pembayaran'] as $col) {
                if (Schema::hasColumn('rental', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
