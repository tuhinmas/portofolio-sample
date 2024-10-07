<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMaximumSettleStatusToStatusFeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('status_fee', function (Blueprint $table) {
            $table->after("percentage", function($table){
                $table->integer("maximum_settle_payment")->nullable();
                $table->integer("minimum_days_marketing_join")->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('status_fee', function (Blueprint $table) {
            $table->dropColumn('maximum_settle_payment');
            $table->dropColumn('minimum_days_marketing_join');
        });
    }
}
