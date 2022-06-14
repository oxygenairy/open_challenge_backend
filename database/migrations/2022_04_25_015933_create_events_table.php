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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('userid')->index();
            $table->string('title');
            $table->string('status');
            $table->string('type');//weekly, one time, reoccurence
            $table->string('key')->nullable();//private events require 5 digit key to register
            $table->integer('capacity');// total number of registration allowed
            $table->timestamp('expiry')->nullable();
            $table->integer('limit');//total questions to be answered
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
        Schema::dropIfExists('events');
    }
};
