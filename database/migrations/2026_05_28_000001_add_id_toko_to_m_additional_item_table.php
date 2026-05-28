<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('m_additional_item')) {
            return;
        }

        if (! Schema::hasColumn('m_additional_item', 'id_toko')) {
            Schema::table('m_additional_item', function (Blueprint $table) {
                $table->unsignedBigInteger('id_toko')->default(0)->after('id');
                $table->index('id_toko');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('m_additional_item') || ! Schema::hasColumn('m_additional_item', 'id_toko')) {
            return;
        }

        Schema::table('m_additional_item', function (Blueprint $table) {
            $table->dropIndex(['id_toko']);
            $table->dropColumn('id_toko');
        });
    }
};
