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
        Schema::table('receiving_good_detail_indirect_sales', function (Blueprint $table) {
            $table->uuid("product_id")->after("note");
            $table->foreign("product_id")
                  ->references("id")
                  ->on("products")
                  ->onUpdate("cascade");
            $table->integer("quantity")->default(0)->after("note");
            $table->integer("quantity_package")->default(0)->after("quantity");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('receiving_good_detail_indirect_sales', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
            $table->dropColumn('quantity');
            $table->dropColumn('quantity_package');
        });
    }
};
