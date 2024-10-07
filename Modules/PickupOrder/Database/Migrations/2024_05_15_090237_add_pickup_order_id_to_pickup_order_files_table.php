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
        Schema::table('pickup_order_files', function (Blueprint $table) {
            $table->dropForeign(["pickup_order_detail_id"]);
            $table->dropColumn('pickup_order_detail_id');
        });

        Schema::table('pickup_order_files', function (Blueprint $table) {
            $table->uuid("pickup_order_id")->after("id");
            $table->foreign('pickup_order_id')
                ->references('id')
                ->on('pickup_orders')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pickup_order_files', function (Blueprint $table) {
            $table->uuid("pickup_order_detail_id");
            $table->foreign('pickup_order_detail_id')
                ->references('id')
                ->on('pickup_order_details')
                ->onDelete('cascade');
        });
        Schema::table('pickup_order_files', function (Blueprint $table) {
            $table->dropForeign(["pickup_order_id"]);
            $table->dropColumn('pickup_order_id');
        });

    }
};
