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
        Schema::create('sales_order_status_fee_shoulds', function (Blueprint $table) {
            $table->id();
            $table->uuid("sales_order_id");
            $table->uuid("status_fee_id");
            $table->timestamps();
            $table->foreign("sales_order_id")
                ->references("id")
                ->on("sales_orders")
                ->onDelete("cascade");

            $table->foreign("status_fee_id")
                ->references("id")
                ->on("status_fee")
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
        Schema::dropIfExists('sales_order_status_fee_shoulds');
    }
};
