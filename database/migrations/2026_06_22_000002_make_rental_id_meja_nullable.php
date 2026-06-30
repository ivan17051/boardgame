<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rental') || ! Schema::hasColumn('rental', 'id_meja')) {
            return;
        }

        $foreignKeys = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'rental'
               AND COLUMN_NAME = 'id_meja'
               AND REFERENCED_TABLE_NAME IS NOT NULL"
        );

        foreach ($foreignKeys as $fk) {
            DB::statement('ALTER TABLE rental DROP FOREIGN KEY `'.$fk->CONSTRAINT_NAME.'`');
        }

        DB::statement('ALTER TABLE rental MODIFY id_meja BIGINT UNSIGNED NULL');
        DB::statement(
            'ALTER TABLE rental ADD CONSTRAINT rental_id_meja_foreign
             FOREIGN KEY (id_meja) REFERENCES m_meja(id) ON DELETE RESTRICT'
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('rental') || ! Schema::hasColumn('rental', 'id_meja')) {
            return;
        }

        DB::statement('ALTER TABLE rental DROP FOREIGN KEY rental_id_meja_foreign');
        DB::statement('ALTER TABLE rental MODIFY id_meja BIGINT UNSIGNED NOT NULL');
        DB::statement(
            'ALTER TABLE rental ADD CONSTRAINT rental_id_meja_foreign
             FOREIGN KEY (id_meja) REFERENCES m_meja(id) ON DELETE RESTRICT'
        );
    }
};
