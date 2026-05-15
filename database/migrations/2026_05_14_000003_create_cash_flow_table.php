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
        if (Schema::hasTable('cash_flow')) {
            return;
        }

        Schema::create('cash_flow', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_rental')->nullable();
            $table->string('tipe_transaksi', 20);
            $table->string('metode_pembayaran', 100)->nullable();
            $table->decimal('total', 15, 3);
            $table->string('keterangan')->nullable();
            $table->dateTime('waktu_pembayaran');
            $table->unsignedBigInteger('idc')->nullable();
            $table->unsignedBigInteger('idm')->nullable();
            $table->dateTime('doc')->nullable();
            $table->dateTime('dom')->nullable();

            $table->unique('id_rental');
            $table->index(['tipe_transaksi', 'waktu_pembayaran']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_flow');
    }
};
