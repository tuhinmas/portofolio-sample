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
        Schema::create('sales_order_origins', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->uuid("direct_id");
            $table->uuid("parent_id");
            $table->uuid("sales_order_detail_id");
            $table->uuid("product_id");
            $table->integer("quantity_from_origin");
            $table->double("direct_price", 20, 2);
            $table->tinyInteger("is_returned")->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("direct_id")
                ->references("id")
                ->on("sales_orders")
                ->omDelete("cascade");

            $table->foreign("parent_id")
                ->references("id")
                ->on("sales_orders")
                ->omDelete("cascade");

            $table->foreign("sales_order_detail_id")
                ->references("id")
                ->on("sales_order_details")
                ->onDelete("cascade");

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
        Schema::dropIfExists('sales_order_origins');
    }
};
