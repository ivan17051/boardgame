<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('meja_booking')) {
            return;
        }

        Schema::create('meja_booking', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('id_meja');
            $table->string('nama_customer');
            $table->string('no_hp', 30)->nullable();
            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_selesai');
            $table->string('status', 20)->default('booked');
            $table->unsignedBigInteger('id_rental')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->string('catatan', 255)->nullable();
            $table->timestamps();

            $table->index(['id_meja', 'status', 'waktu_mulai']);
            $table->index('waktu_mulai');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meja_booking');
    }
};
