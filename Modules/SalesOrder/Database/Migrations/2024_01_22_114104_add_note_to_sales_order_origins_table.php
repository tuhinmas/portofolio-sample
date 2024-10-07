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
            $table->string("note")->after("level")->nullable();
            $table->uuid("sales_order_detail_return_id")->after("sales_order_detail_id")->nullable();
            $table->foreign("sales_order_detail_return_id")
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
        Schema::table('sales_order_origins', function (Blueprint $table) {
            $table->dropColumn('note');
            $table->dropForeign(["sales_order_detail_return_id"]);
            $table->dropColumn('sales_order_detail_return_id');
        });
    }
};
