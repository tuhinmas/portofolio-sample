<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStorePointToSalesOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->after("status", function($table){
                $table->integer("store_point")->nullable();
                $table->integer("marketing_point")->nullable();
                $table->double("marketing_fee")->nullable();
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
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('store_point');
            $table->dropColumn('marketing_point');
            $table->dropColumn('marketing_fee');
        });
    }
}
