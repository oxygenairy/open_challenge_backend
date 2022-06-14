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
        Schema::create('event_attribs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 60);
            $table->integer('sequence');
            $table->integer('max');
            $table->integer('house_percent')->default(20);
            $table->integer('referal_percent')->default(3);
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
        Schema::dropIfExists('event_attribs');
    }
};
