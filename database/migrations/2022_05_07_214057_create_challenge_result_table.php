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
        Schema::create('challenge_result', function (Blueprint $table) {
            $table->id();
            $table->string('challenge_id', 22)->index();
            $table->integer('total_question');//userid of the challenger
            $table->integer('player1_score')->default(0);
            $table->integer('player2_score')->default(0);
            $table->integer('player1_progress')->default(0);
            $table->integer('player2_progress')->default(0);
            $table->integer('player1_bot')->default(0);; // level of bot during event
            $table->integer('player2_bot')->default(0);
            $table->string('player1_perks')->default('non');
            $table->string('player2_perks')->default('non');
            $table->integer('player1_time')->default(0);
            $table->integer('player2_time')->default(0);
            $table->string('status', 12)->default('loading'); //setup, pending, awaiting, taken, completed, canceled
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
        Schema::dropIfExists('challenge_result');
    }
};
