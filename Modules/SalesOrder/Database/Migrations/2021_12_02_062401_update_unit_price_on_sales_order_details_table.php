<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUnitPriceOnSalesOrderDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::table('sales_order_details', function (Blueprint $table) {
            $table->uuid("agency_level")->change();
        });

        Schema::table('sales_order_details', function (Blueprint $table) {
            $table->renameColumn("store_point", "retail_point");
            $table->renameColumn("agency_level", "agency_level_id");
            $table->decimal("unit_price", 15, 2)->change();
            $table->decimal("total", 15, 2)->change();
            $table->after("unit_price", function($table){
                $table->double("discount", 15, 2)->nullable();
            });
            $table->foreign("agency_level_id")
                  ->references("id")
                  ->on("agency_levels")
                  ->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales_order_details', function (Blueprint $table) {
            $table->dropColumn('discount');
            $table->dropForeign(['agency_level_id']);
            $table->renameColumn("retail_point", "store_point");
            $table->renameColumn("agency_level_id", "agency_level");
        });
    }
}
