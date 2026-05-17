<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('m_users')) {
            return;
        }

        if (Schema::hasColumn('m_users', 'id_toko')) {
            return;
        }

        Schema::table('m_users', function (Blueprint $table) {
            $table->unsignedBigInteger('id_toko')->default(0)->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('m_users') || ! Schema::hasColumn('m_users', 'id_toko')) {
            return;
        }

        Schema::table('m_users', function (Blueprint $table) {
            $table->dropColumn('id_toko');
        });
    }
};
