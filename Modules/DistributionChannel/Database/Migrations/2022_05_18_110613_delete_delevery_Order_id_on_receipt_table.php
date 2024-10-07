<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('receipts_details', function (Blueprint $table) {
            $table->dropForeign('delivery_order_receipts_id_delivery_orders_foreign');
            // $table->dropForeign(['id_delivery_orders']);
            $table->dropColumn('id_delivery_orders');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('receipts_details', function (Blueprint $table) {
            $table->uuid("id_delivery_orders")->after("id");
            $table->foreign("id_delivery_orders")
                ->references("id")
                ->on("delivery_orders");
        });
    }
};
