<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('m_rental_promo') || ! Schema::hasColumn('m_rental_promo', 'promo_duration_limit')) {
            return;
        }

        DB::statement('ALTER TABLE m_rental_promo MODIFY promo_duration_limit DECIMAL(8,2) NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('m_rental_promo') || ! Schema::hasColumn('m_rental_promo', 'promo_duration_limit')) {
            return;
        }

        DB::statement('ALTER TABLE m_rental_promo MODIFY promo_duration_limit DECIMAL(8,2) NOT NULL');
    }
};
