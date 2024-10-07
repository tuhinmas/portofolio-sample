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
        Schema::table('receiving_goods', function (Blueprint $table) {
            $table->after("id", function ($table) {
                $table->uuid("delivery_order_id")->nullable();
                $table->timestamp("date_received")->useCurrent();
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
        Schema::table('receiving_goods', function (Blueprint $table) {
            $table->dropForeign(['delivery_order_id']);
            $table->dropColumn('delivery_order_id');
            $table->dropColumn('date_received');
        });
    }
};
