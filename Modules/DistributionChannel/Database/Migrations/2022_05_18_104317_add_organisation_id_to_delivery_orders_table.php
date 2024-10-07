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
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->renameColumn('date_delivered', 'date_delivery');
        });

        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->uuid("dispatch_order_id")->after("id")->nullable();
            $table->foreign("dispatch_order_id")
                ->references("id")
                ->on("discpatch_order");

            $table->uuid("organisation_id")->after("dealer_id")->nullable();
            $table->foreign("organisation_id")
                ->references("id")
                ->on("organisations");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->renameColumn('date_delivery', 'date_delivered');
            $table->dropForeign(['dispatch_order_id']);
            $table->dropColumn('dispatch_order_id');
            $table->dropForeign(['organisation_id']);
            $table->dropColumn('organisation_id');
        });
    }
};
