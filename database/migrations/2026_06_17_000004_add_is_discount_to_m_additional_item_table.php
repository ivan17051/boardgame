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

        if (Schema::hasColumn('m_additional_item', 'is_discount')) {
            return;
        }

        Schema::table('m_additional_item', function (Blueprint $table) {
            $table->boolean('is_discount')->default(false)->after('harga');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('m_additional_item') || ! Schema::hasColumn('m_additional_item', 'is_discount')) {
            return;
        }

        Schema::table('m_additional_item', function (Blueprint $table) {
            $table->dropColumn('is_discount');
        });
    }
};
