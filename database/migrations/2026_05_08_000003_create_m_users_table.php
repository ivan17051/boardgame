<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('m_users')) {
            return;
        }

        Schema::create('m_users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_toko')->default(0);
            $table->string('nama');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('remember_token', 255)->nullable();
            $table->string('role', 20);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_hidden')->default(false);
            $table->timestamp('doc')->useCurrent();
            $table->timestamp('dom')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_users');
    }
};
