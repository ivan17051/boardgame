<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental', function (Blueprint $table) {
            if (! Schema::hasColumn('rental', 'guest_token')) {
                $table->string('guest_token', 64)->nullable()->unique()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rental', function (Blueprint $table) {
            if (Schema::hasColumn('rental', 'guest_token')) {
                $table->dropUnique(['guest_token']);
                $table->dropColumn('guest_token');
            }
        });
    }
};
