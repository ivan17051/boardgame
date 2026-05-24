<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_flow', function (Blueprint $table) {
            if (! Schema::hasColumn('cash_flow', 'jumlah_bayar')) {
                $table->decimal('jumlah_bayar', 15, 3)->nullable()->after('total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cash_flow', function (Blueprint $table) {
            if (Schema::hasColumn('cash_flow', 'jumlah_bayar')) {
                $table->dropColumn('jumlah_bayar');
            }
        });
    }
};
