<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rental')) {
            return;
        }

        Schema::table('rental', function (Blueprint $table) {
            if (! Schema::hasColumn('rental', 'promo_tgl_awal')) {
                $table->date('promo_tgl_awal')->nullable()->after('promo_jam_selesai');
            }
            if (! Schema::hasColumn('rental', 'promo_tgl_akhir')) {
                $table->date('promo_tgl_akhir')->nullable()->after('promo_tgl_awal');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('rental')) {
            return;
        }

        Schema::table('rental', function (Blueprint $table) {
            foreach (['promo_tgl_akhir', 'promo_tgl_awal'] as $col) {
                if (Schema::hasColumn('rental', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
