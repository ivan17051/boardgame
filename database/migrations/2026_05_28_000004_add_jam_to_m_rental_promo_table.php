<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('m_rental_promo')) {
            return;
        }

        Schema::table('m_rental_promo', function (Blueprint $table) {
            if (! Schema::hasColumn('m_rental_promo', 'jam_mulai')) {
                $table->time('jam_mulai')->default('00:00:00')->after('promo_duration_limit');
            }
            if (! Schema::hasColumn('m_rental_promo', 'jam_selesai')) {
                $table->time('jam_selesai')->default('23:59:59')->after('jam_mulai');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('m_rental_promo')) {
            return;
        }

        Schema::table('m_rental_promo', function (Blueprint $table) {
            foreach (['jam_selesai', 'jam_mulai'] as $col) {
                if (Schema::hasColumn('m_rental_promo', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
