<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('m_rental_promo')) {
            return;
        }

        Schema::create('m_rental_promo', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('id_toko');
            $table->string('nama');
            $table->decimal('promo_hourly_rate', 15, 3);
            $table->decimal('promo_duration_limit', 8, 2)->comment('max billed hours at promo rate');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('idc')->nullable();
            $table->unsignedBigInteger('idm')->nullable();
            $table->dateTime('doc')->nullable();
            $table->dateTime('dom')->nullable();

            $table->index('id_toko');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_rental_promo');
    }
};
