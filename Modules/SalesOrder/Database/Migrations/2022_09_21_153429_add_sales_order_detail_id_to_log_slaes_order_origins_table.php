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
        Schema::table('log_sales_order_origins', function (Blueprint $table) {
            $table->uuid("sales_order_id")->nullable()->change();
            $table->uuid("sales_order_detail_id")->nullable()->after("sales_order_id");
            $table->foreign("sales_order_detail_id")
                ->references("id")
                ->on("sales_order_details")
                ->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_sales_order_origins', function (Blueprint $table) {
            $table->dropForeign(['sales_order_detail_id']);
            $table->dropColumn('sales_order_detail_id');
        });
    }
};
