<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('adjustment_stock', function (Blueprint $table) {
            $table->after("dealer_id", function($table){
                $table->integer("product_in_warehouse")->nullable()->comment("products ready on warehouse");
                $table->integer("product_unreceived_by_distributor")->nullable()->comment("products purchase by distributor and has not received by distributor");
                $table->integer("product_undelivered_by_distributor")->nullable()->comment("product sales by distributor and has not received by retailer");
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
        Schema::table('adjustment_stock', function (Blueprint $table) {
            $table->dropColumn('product_in_warehouse');
            $table->dropColumn('product_unreceived_by_distributor');
            $table->dropColumn('product_undelivered_by_distributor');
        });
    }
};
