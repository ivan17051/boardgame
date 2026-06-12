<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('m_rental_promo')) {
            return;
        }

        DB::table('m_rental_promo')
            ->where('tgl_awal', '2020-01-01')
            ->where('tgl_akhir', '2099-12-31')
            ->update(['tgl_awal' => null, 'tgl_akhir' => null]);
    }

    public function down(): void
    {
        // no-op
    }
};
