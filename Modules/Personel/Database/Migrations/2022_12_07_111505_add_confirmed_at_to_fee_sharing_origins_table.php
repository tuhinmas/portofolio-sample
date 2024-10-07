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

        Schema::table('fee_sharing_so_origins', function (Blueprint $table) {
            $table->uuid("personel_id")->nullable()->change();
            $table->uuid("sales_order_origin_id")->nullable()->change();

            $table->uuid("sales_order_id")->after("sales_order_origin_id");
            $table->foreign("sales_order_id")
                ->references("id")
                ->on("sales_orders")
                ->onDelete("cascade");
        });

        Schema::table('fee_sharing_so_origins', function (Blueprint $table) {
            $table->uuid("sales_order_detail_id")->after("sales_order_id");
            $table->foreign("sales_order_detail_id")
                ->references("id")
                ->on("sales_order_details")
                ->onDelete("cascade");

            $table->timestamp("confirmed_at")->nullable()->after("fee_status");
            $table->string("fee_status")->nullable()->change();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fee_sharing_so_origins', function (Blueprint $table) {
            $table->dropForeign(['sales_order_id']);
            $table->dropColumn('sales_order_id');
    
            $table->dropForeign(['sales_order_detail_id']);
            $table->dropColumn('sales_order_detail_id');
            $table->dropColumn('confirmed_at');
        });

    }
};
