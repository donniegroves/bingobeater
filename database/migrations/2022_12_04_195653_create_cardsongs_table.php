<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('card_songs', function (Blueprint $table) {
            $table->id();
            $table->integer('game_id');
            $table->integer('card_id');
            $table->tinyInteger('round');
            $table->tinyInteger('col');
            $table->tinyInteger('row');
            $table->string('artist');
            $table->string('song_title');
            $table->boolean('played');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('card_songs');
    }
};
