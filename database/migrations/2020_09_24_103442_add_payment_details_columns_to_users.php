<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentDetailsColumnsToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('country')->after('bio')->nullable();
            $table->string('account_name')->after('country')->nullable();
            $table->integer('sort_code')->after('account_name')->nullable();
            $table->integer('account_number')->after('sort_code')->nullable();
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
            $table->dropColumn('country');
            $table->dropColumn('account_name');
            $table->dropColumn('sort_code');
            $table->dropColumn('account_number');
        });
    }
}
