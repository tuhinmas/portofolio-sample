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
        Schema::table('fee_target_sharing_so_origins', function (Blueprint $table) {
            $table->uuid("sales_order_detail_id")->after("sales_order_origin_id");
            $table->foreign("sales_order_detail_id")
                ->references("id")
                ->on("sales_order_details")
                ->onDelete("cascade");
        });

        Schema::table('fee_target_sharing_so_origins', function (Blueprint $table) {
            $table->uuid("status_fee_id")->after("sales_order_detail_id");
            $table->foreign("status_fee_id")
                ->references("id")
                ->on("status_fee")
                ->onDelete("cascade");
        });

        Schema::table('fee_target_sharing_so_origins', function (Blueprint $table) {
            $table->double("status_fee_percentage", 5, 2)->after("status_fee_id");
            $table->timestamp("confirmed_at")->after("is_returned")->nullable();
        });

        Schema::table('fee_target_sharing_so_origins', function (Blueprint $table) {
            $table->uuid("personel_id")->nullable()->change();
        });

        Schema::table('fee_target_sharing_so_origins', function (Blueprint $table) {
            $table->uuid("product_id")->after("sales_order_detail_id");
            $table->foreign("product_id")
                ->references("id")
                ->on("products")
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
        Schema::table('fee_target_sharing_so_origins', function (Blueprint $table) {
            $table->dropForeign(['sales_order_detail_id']);
            $table->dropColumn('sales_order_detail_id');

            $table->dropForeign(['status_fee_id']);
            $table->dropColumn('status_fee_id');

            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');

            $table->dropColumn('status_fee_percentage');
            $table->dropColumn('confirmed_at');
        });
    }
};
