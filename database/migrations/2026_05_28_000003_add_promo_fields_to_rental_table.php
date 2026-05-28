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
            if (! Schema::hasColumn('rental', 'id_promo')) {
                $table->unsignedBigInteger('id_promo')->nullable()->after('harga');
            }
            if (! Schema::hasColumn('rental', 'promo_nama')) {
                $table->string('promo_nama')->nullable()->after('id_promo');
            }
            if (! Schema::hasColumn('rental', 'promo_hourly_rate')) {
                $table->decimal('promo_hourly_rate', 15, 3)->nullable()->after('promo_nama');
            }
            if (! Schema::hasColumn('rental', 'promo_duration_limit')) {
                $table->decimal('promo_duration_limit', 8, 2)->nullable()->after('promo_hourly_rate');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('rental')) {
            return;
        }

        Schema::table('rental', function (Blueprint $table) {
            $cols = ['promo_duration_limit', 'promo_hourly_rate', 'promo_nama', 'id_promo'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('rental', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
