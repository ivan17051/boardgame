<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental', function (Blueprint $table) {
            if (! Schema::hasColumn('rental', 'tipe_customer')) {
                $table->string('tipe_customer', 20)->default('non_member')->after('nama_customer');
            }
            if (! Schema::hasColumn('rental', 'total_harga_sewa')) {
                $table->decimal('total_harga_sewa', 15, 3)->nullable()->after('total_harga');
            }
            if (! Schema::hasColumn('rental', 'total_harga_additional')) {
                $table->decimal('total_harga_additional', 15, 3)->default(0)->after('total_harga_sewa');
            }
        });

        if (! Schema::hasTable('rental_additional_item')) {
            Schema::create('rental_additional_item', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_rental');
                $table->unsignedBigInteger('id_additional_item')->nullable();
                $table->string('nama');
                $table->decimal('harga', 15, 3);
                $table->unsignedInteger('qty')->default(1);
                $table->decimal('subtotal', 15, 3);

                $table->index('id_rental');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_additional_item');

        Schema::table('rental', function (Blueprint $table) {
            foreach (['tipe_customer', 'total_harga_sewa', 'total_harga_additional'] as $col) {
                if (Schema::hasColumn('rental', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
