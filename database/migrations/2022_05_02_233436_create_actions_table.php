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
        Schema::create('actions', function (Blueprint $table) {
            $table->id();
            $table->string('event');
            $table->string('category', 30);
            $table->string('description')->nullable();
            $table->integer('coins');
            $table->integer('tokens');
            $table->integer('tickets');
            $table->integer('energy');
            $table->integer('level');
            $table->integer('referer_coins');
            $table->string('direction', 3);
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
        Schema::dropIfExists('actions');
    }
};
