<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameDispatchOrderIdOnReceivingGoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('receiving_goods', function (Blueprint $table) {
            $table->renameColumn('dispatch_order_id', 'shipping_number');
        });
        Schema::table('receiving_goods', function (Blueprint $table) {
            $table->after("shipping_number", function($table){
                $table->uuid("shipping_id")->nullable();
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
        Schema::table('receiving_goods', function (Blueprint $table) {
            $table->renameColumn('shipping_number', 'dispatch_order_id');
            $table->dropColumn('shipping_id');
        });
    }
}
