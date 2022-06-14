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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('userid');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('display')->default('avatar0');
            $table->string('password');
            $table->integer('role')->default(1); //1 = player, 2 = moderator, 3 = admin, 4 = super admin
            $table->string('referer')->default('non');
            $table->string('status')->default('pending');
            $table->timestamp('last_login_at')->nullable();
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
        Schema::dropIfExists('users');
    }
};
