<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUrlFieldToUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('subscription_price')->after('profile_video')->nullable();
            $table->string('twitter_url')->after('subscription_price')->nullable();
            $table->string('amazon_url')->after('twitter_url')->nullable();
            $table->string('bio')->after('amazon_url')->nullable();
            $table->string('tags')->after('bio')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('subscription_price');
            $table->dropColumn('twitter_url');
            $table->dropColumn('amazon_url');
            $table->dropColumn('bio');
            $table->dropColumn('tags');
        });
    }
}
