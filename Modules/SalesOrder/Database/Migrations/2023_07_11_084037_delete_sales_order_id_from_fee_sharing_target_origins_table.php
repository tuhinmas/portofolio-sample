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
        Schema::table('fee_target_sharings', function (Blueprint $table) {
            $table->dropForeign(['sales_order_id']);
            $table->dropForeign(['sales_order_detail_id']);
            $table->dropColumn('sales_order_id');
            $table->dropColumn('sales_order_detail_id');
            $table->dropColumn('quantity_unit');
            $table->dropColumn('fee_percentage');
            $table->dropColumn('fee_nominal');
            $table->dropColumn('is_checked');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fee_target_sharings', function (Blueprint $table) {
            $table->after("position_id", function ($table) {
                $table->uuid("sales_order_id")->nullable();
                $table->uuid("sales_order_detail_id")->nullable();
                $table->double("quantity_unit", 20, 2);
                $table->double("fee_percentage", 20, 2);
                $table->double("fee_nominal", 20, 2);
                $table->boolean("is_checked");

                $table->foreign("sales_order_id")
                    ->references("id")
                    ->on("sales_orders")
                    ->onDelete("cascade");
                    
                $table->foreign("sales_order_detail_id")
                    ->references("id")
                    ->on("sales_order_details")
                    ->onDelete("cascade");
            });
        });
    }
};
