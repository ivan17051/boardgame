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
        Schema::create('m_meja', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_toko');
            $table->string('nama');
            $table->decimal('harga', 15, 3)->default(0);
            $table->unsignedBigInteger('idc')->nullable();
            $table->dateTime('doc')->nullable();
            $table->unsignedBigInteger('idm')->nullable();
            $table->dateTime('dom')->nullable();

            $table->foreign('id_toko')->references('id')->on('m_toko')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('m_meja');
    }
};
