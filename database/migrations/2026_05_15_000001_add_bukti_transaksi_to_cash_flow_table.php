<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('cash_flow')) {
            return;
        }

        if (Schema::hasColumn('cash_flow', 'bukti_transaksi')) {
            return;
        }

        Schema::table('cash_flow', function (Blueprint $table) {
            $table->string('bukti_transaksi', 512)->nullable()->after('metode_pembayaran');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('cash_flow') || ! Schema::hasColumn('cash_flow', 'bukti_transaksi')) {
            return;
        }

        Schema::table('cash_flow', function (Blueprint $table) {
            $table->dropColumn('bukti_transaksi');
        });
    }
};
