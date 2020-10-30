<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubscriptionPlanFollowTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('follow', function (Blueprint $table) {
            $table->bigInteger('subscription_plan')->unsigned()->index();
            $table->foreign('subscription_plan')
                ->references('id')->on('subscription_plans')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('follow', function (Blueprint $table) {
            $table->dropColumn('subscription_plan');
        });
    }
}
