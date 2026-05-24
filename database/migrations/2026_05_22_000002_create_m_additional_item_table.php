<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('m_additional_item')) {
            return;
        }

        Schema::create('m_additional_item', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->decimal('harga', 15, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('idc')->nullable();
            $table->unsignedBigInteger('idm')->nullable();
            $table->dateTime('doc')->nullable();
            $table->dateTime('dom')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_additional_item');
    }
};
