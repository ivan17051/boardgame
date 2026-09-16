<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRentalMahjongScoringTables extends Migration
{
    public function up()
    {
        Schema::create('rental_mahjong_session', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('rental_id')->unique();
            $table->integer('id_meja')->nullable();
            $table->string('status', 20)->default('open');
            $table->string('access_token', 64)->unique();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->foreign('rental_id')->references('id')->on('rental')->onDelete('cascade');
            $table->index('id_meja');
        });

        Schema::create('rental_mahjong_player', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('session_id');
            $table->unsignedTinyInteger('seat');
            $table->string('nama', 255);
            $table->boolean('is_renter')->default(false);
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('rental_mahjong_session')->onDelete('cascade');
            $table->unique(['session_id', 'seat']);
        });

        Schema::create('rental_mahjong_hand', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('session_id');
            $table->unsignedInteger('hand_no');
            $table->unsignedTinyInteger('winner_seat')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('session_id')->references('id')->on('rental_mahjong_session')->onDelete('cascade');
            $table->unique(['session_id', 'hand_no']);
        });

        Schema::create('rental_mahjong_hand_score', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('hand_id');
            $table->unsignedInteger('player_id');
            $table->integer('poin');

            $table->foreign('hand_id')->references('id')->on('rental_mahjong_hand')->onDelete('cascade');
            $table->foreign('player_id')->references('id')->on('rental_mahjong_player')->onDelete('cascade');
            $table->unique(['hand_id', 'player_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('rental_mahjong_hand_score');
        Schema::dropIfExists('rental_mahjong_hand');
        Schema::dropIfExists('rental_mahjong_player');
        Schema::dropIfExists('rental_mahjong_session');
    }
}
