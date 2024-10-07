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
        Schema::table('sales_order_history_change_statuses', function (Blueprint $table) {

            $table->after("sales_order_id", function ($table) {
                $table->uuid("delivery_order_id")->nullable();
                $table->foreign("delivery_order_id")
                    ->references("id")
                    ->on("delivery_orders")
                    ->onDelete("cascade");
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
        Schema::table('sales_order_history_change_statuses', function (Blueprint $table) {
            $table->dropForeign(['delivery_order_id']);
            $table->dropColumn('delivery_order_id');
        });
    }
};
