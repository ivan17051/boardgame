<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        $rows = DB::select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
            [$table, $indexName]
        );

        return ! empty($rows);
    }

    public function up(): void
    {
        Schema::table('cash_flow', function (Blueprint $table) {
            if (! Schema::hasColumn('cash_flow', 'kategori_pendapatan')) {
                $table->string('kategori_pendapatan', 40)->nullable()->after('tipe_transaksi');
            }
        });

        try {
            Schema::table('cash_flow', function (Blueprint $table) {
                $table->dropUnique(['id_rental']);
            });
        } catch (\Throwable $e) {
            // unique index may already be removed
        }

        Schema::table('cash_flow', function (Blueprint $table) {
            if (! $this->indexExists('cash_flow', 'cash_flow_rental_kategori_idx')) {
                $table->index(['id_rental', 'kategori_pendapatan'], 'cash_flow_rental_kategori_idx');
            }
            if (! $this->indexExists('cash_flow', 'cash_flow_kategori_pendapatan_index')) {
                $table->index('kategori_pendapatan');
            }
        });

        DB::table('cash_flow')
            ->where('tipe_transaksi', 'income')
            ->whereNull('kategori_pendapatan')
            ->update(['kategori_pendapatan' => 'sewa_meja']);
    }

    public function down(): void
    {
        Schema::table('cash_flow', function (Blueprint $table) {
            $table->dropIndex('cash_flow_rental_kategori_idx');
            $table->dropIndex(['kategori_pendapatan']);
        });

        Schema::table('cash_flow', function (Blueprint $table) {
            if (Schema::hasColumn('cash_flow', 'kategori_pendapatan')) {
                $table->dropColumn('kategori_pendapatan');
            }
            $table->unique('id_rental');
        });
    }
};
