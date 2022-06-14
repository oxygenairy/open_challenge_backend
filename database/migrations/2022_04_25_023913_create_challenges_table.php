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
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->string('challenge_id')->unique(); //challenge_id
            $table->string('player1', 22)->index(); //userids
            $table->string('player2', 22)->index()->nullable();
            $table->string('title')->index();
            $table->string('type', 7)->default('public');// public, private
            $table->string('password')->nullable();
            $table->integer('amount');
            $table->string('status', 10)->default('pending'); //pending, awaiting, ongoing, cancelled, completed, 
            $table->string('winner')->nullable(); //player1, player2, draw
            $table->timestamp('expiry')->nullable();
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
        Schema::dropIfExists('challenges');
    }
};
