<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('m_rental_promo')) {
            return;
        }

        Schema::table('m_rental_promo', function (Blueprint $table) {
            if (! Schema::hasColumn('m_rental_promo', 'tgl_awal')) {
                $table->date('tgl_awal')->nullable()->after('jam_selesai');
            }
            if (! Schema::hasColumn('m_rental_promo', 'tgl_akhir')) {
                $table->date('tgl_akhir')->nullable()->after('tgl_awal');
            }
        });

        if (Schema::hasColumn('m_rental_promo', 'tgl_awal')) {
            DB::table('m_rental_promo')
                ->whereNull('tgl_awal')
                ->update(['tgl_awal' => '2020-01-01', 'tgl_akhir' => '2099-12-31']);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('m_rental_promo')) {
            return;
        }

        Schema::table('m_rental_promo', function (Blueprint $table) {
            foreach (['tgl_akhir', 'tgl_awal'] as $col) {
                if (Schema::hasColumn('m_rental_promo', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
