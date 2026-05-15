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
        Schema::table('m_meja', function (Blueprint $table) {
            if (! Schema::hasColumn('m_meja', 'status')) {
                $table->string('status', 20)->default('active')->after('harga');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('m_meja', function (Blueprint $table) {
            if (Schema::hasColumn('m_meja', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
