<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
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
            $table->string('first_name');
            $table->string('last_name');
            $table->string('display_name');
            $table->string('username')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified')->nullable();
            $table->string('password');
            $table->string('profile')->nullable();
            $table->string('cover')->nullable();
            $table->string('location')->nullable();
            $table->string('category');
            $table->integer('term')->default(0);
            $table->integer('year_old')->default(0);
            $table->integer('two_factor')->default(0);
            $table->rememberToken();
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
}
