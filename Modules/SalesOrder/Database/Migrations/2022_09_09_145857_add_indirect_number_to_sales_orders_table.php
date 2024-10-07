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
        Schema::table('sales_orders', function (Blueprint $table) {
            // $table->bigInteger("order_number")->after("recipient_phone_number")->nullable();
        });
        
        Schema::table('sales_orders', function (Blueprint $table) {
            // DB::statement("ALTER TABLE sales_orders CHANGE `order_number` `order_number` AUTO_INCREMENT");
            DB::statement('ALTER Table sales_orders add order_number BIGINT NOT NULL UNIQUE AUTO_INCREMENT AFTER reference_number;');
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
            $table->dropColumn('order_number');
        });
    }
};
