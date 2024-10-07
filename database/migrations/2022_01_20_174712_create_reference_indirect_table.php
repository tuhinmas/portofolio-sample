<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferenceIndirectTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reference_indirects', function (Blueprint $table) {
            $table->id();
            $table->uuid("sales_order_id");
            $table->uuid("sales_order_details_id");
            $table->uuid("reference_sales_order_details_id");
            $table->uuid("reference_sales_order_id");
            $table->foreign("sales_order_details_id")
                ->references("id")
                ->on("sales_order_details")
                ->onDelete("cascade");
            $table->foreign("reference_sales_order_details_id")
                ->references("id")
                ->on("sales_order_details")
                ->onDelete("cascade");
            $table->foreign("sales_order_id")
                ->references("id")
                ->on("sales_orders");
            $table->foreign("reference_sales_order_id")
                ->references("id")
                ->on("sales_orders")
                ->onDelete("cascade");
            $table->string('status')->default('request');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reference_indirects');
    }
}
