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
            $table->uuid("sales_order_detail_parent_id")->after("parent_id")->nullable();
            $table->uuid("sales_order_detail_direct_id")->after("direct_id")->nullable();
            $table->foreign("sales_order_detail_parent_id")
                ->references("id")
                ->on("sales_order_details");

            $table->foreign("sales_order_detail_direct_id")
                ->references("id")
                ->on("sales_order_details");

            $table->decimal("direct_price", 20, 2)->nullable()->change();
            $table->bigInteger("current_stock")->default(0)->after("product_id");
            $table->integer("quantity_order")->after("product_id");
            $table->tinyInteger("type")->nullable()->after("sales_order_id");
            $table->tinyInteger("is_splited_origin")->default(0)->after("quantity_from_origin");
            $table->bigInteger("lack_of_stock")->default(0)->after("quantity_from_origin");
            $table->bigInteger("stock_ready")->after("quantity_from_origin")->default(0);
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
            $table->dropForeign(['sales_order_detail_parent_id']);
            $table->dropColumn('sales_order_detail_parent_id');
            $table->dropForeign(['sales_order_detail_direct_id']);
            $table->dropColumn('sales_order_detail_direct_id');
            $table->dropColumn('current_stock');
            $table->dropColumn('quantity_order');
            $table->dropColumn('type');
            $table->dropColumn('is_splited_origin');
            $table->dropColumn('lack_of_stock');
            $table->dropColumn('stock_ready');
        });
    }
};
