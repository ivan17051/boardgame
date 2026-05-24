<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('m_meja', function (Blueprint $table) {
            if (! Schema::hasColumn('m_meja', 'harga_member')) {
                $table->decimal('harga_member', 15, 3)->nullable()->after('harga');
            }
        });
    }

    public function down(): void
    {
        Schema::table('m_meja', function (Blueprint $table) {
            if (Schema::hasColumn('m_meja', 'harga_member')) {
                $table->dropColumn('harga_member');
            }
        });
    }
};
