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
            if (! Schema::hasColumn('rental', 'promo_jam_mulai')) {
                $table->time('promo_jam_mulai')->nullable()->after('promo_duration_limit');
            }
            if (! Schema::hasColumn('rental', 'promo_jam_selesai')) {
                $table->time('promo_jam_selesai')->nullable()->after('promo_jam_mulai');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('rental')) {
            return;
        }

        Schema::table('rental', function (Blueprint $table) {
            foreach (['promo_jam_selesai', 'promo_jam_mulai'] as $col) {
                if (Schema::hasColumn('rental', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
