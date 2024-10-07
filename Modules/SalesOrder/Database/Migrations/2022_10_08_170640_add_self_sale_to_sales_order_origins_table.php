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
        Schema::table('sales_order_origins', function (Blueprint $table) {
            $table->after("quantity_from_origin", function($table){
                $table->integer("first_stock")->nullable();
                $table->integer("self_sales")->nullable();
                $table->integer("stock_opname")->nullable();
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
        Schema::table('sales_order_origins', function (Blueprint $table) {
            $table->dropColumn('first_stock');
            $table->dropColumn('self_sales');
            $table->dropColumn('stock_opname');
        });
    }
};
