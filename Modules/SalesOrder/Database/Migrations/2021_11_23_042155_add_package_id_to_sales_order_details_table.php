<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPackageIdToSalesOrderDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales_order_details', function (Blueprint $table) {
            $table->after("product_id", function($table){
                $table->uuid("package_id")->nullable();
                $table->foreign("package_id")
                      ->references("id")
                      ->on("packages");
            });
        });
        Schema::table('sales_order_details', function (Blueprint $table) {
            $table->after("package_id", function($table){
                $table->integer("quantity_on_package")->nullable();
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
        Schema::table('sales_order_details', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropColumn('package_id');
            $table->dropColumn('quantity_on_package');
        });
    }
}
