<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPositionIdToSalesOrderFeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales_order_fee', function (Blueprint $table) {
            $table->renameColumn('peronel_id', 'personel_id');
            $table->uuid("position_id")->after("sales_order_id");
            $table->foreign("position_id")
                  ->references("id")
                  ->on("positions");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales_order_fee', function (Blueprint $table) {
            $table->renameColumn('personel_id', 'peronel_id');
            $table->dropForeign(['position_id']);
            $table->dropColumn('position_id');
        });
    }
}
