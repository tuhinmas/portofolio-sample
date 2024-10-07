<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFeeScToFeePositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fee_positions', function (Blueprint $table) {
            $table->after("fee_cash_minimum_order", function($table){
                $table->double("fee_sc_on_order")->nullalbe();
                $table->integer("maximum_settle_days")->nullable();
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
        Schema::table('fee_positions', function (Blueprint $table) {
            $table->dropColumn('fee_sc_on_order');
            $table->dropColumn('maximum_settle_days');
        });
    }
}
