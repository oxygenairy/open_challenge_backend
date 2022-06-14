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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('userid')->index();
            $table->integer('tokens')->default('0');
            $table->integer('coins')->default('0');
            $table->integer('energy')->default('0');
            $table->integer('tickets')->default('0');
            $table->integer('level')->default('0');
            $table->integer('referer_coins')->default('0');
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
        Schema::dropIfExists('accounts');
    }
};
