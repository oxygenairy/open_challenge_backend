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
        Schema::create('houses', function (Blueprint $table) {
            $table->id();
            $table->integer('bonus_coins')->default(0);
            $table->integer('bonus_tokens')->default(0);
            $table->integer('bonus_tickets')->default(0);
            $table->integer('bonus_energy')->default(0);
            $table->integer('inflow_token')->default(0);//token bought with real money by players
            $table->integer('outflow_coins')->default(0);//coins paid out total
            $table->integer('credit_coins')->default(0);//coins in circulation
            $table->integer('house_coins')->default(0);//profit for house
            $table->integer('insurance_coins')->default(0);//insurance coins
            $table->integer('event_coins')->default(0);//call charges for events storage
            $table->integer('challenge_coins')->default(0);//all the coins currently being waged in challenges
            $table->integer('reward_coins')->default(0);// coins used for event reward(10% reward, 1% registration fee)
            //exchange values
            $table->integer('coin_per_token')->default(1500);//amount of coin per token
            $table->integer('ticket_per_token')->default(1);//amount of tickets per token
            $table->integer('coin_per_ticket')->default(1500);//amount of coins per ticket;
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
        Schema::dropIfExists('houses');
    }
};
