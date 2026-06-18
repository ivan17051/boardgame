<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('m_users')) {
            return;
        }

        if (Schema::hasColumn('m_users', 'is_hidden')) {
            return;
        }

        Schema::table('m_users', function (Blueprint $table) {
            $table->boolean('is_hidden')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('m_users') || ! Schema::hasColumn('m_users', 'is_hidden')) {
            return;
        }

        Schema::table('m_users', function (Blueprint $table) {
            $table->dropColumn('is_hidden');
        });
    }
};
