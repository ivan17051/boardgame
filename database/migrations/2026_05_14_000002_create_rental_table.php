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
        if (Schema::hasTable('rental')) {
            return;
        }

        Schema::create('rental', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_meja');
            $table->string('nama_customer');
            $table->dateTime('waktu_start');
            $table->dateTime('waktu_end')->nullable();
            $table->decimal('total_durasi', 12, 2)->nullable()->comment('minutes');
            $table->decimal('harga', 15, 3)->nullable()->comment('snapshot per jam');
            $table->decimal('total_harga', 15, 3)->nullable();
            $table->string('status', 20)->default('active');

            $table->foreign('id_meja')->references('id')->on('m_meja')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental');
    }
};
