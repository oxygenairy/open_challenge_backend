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
        Schema::create('challenge_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('challenge');
            $table->string('player1');
            $table->string('player2')->nullable();
            $table->string('winner')->nullable();
            $table->integer('total')->default(0);
            $table->string('status')->default('pending'); //cancelled, pending, ongoing, completed,
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
        Schema::dropIfExists('challenge_accounts');
    }
};
