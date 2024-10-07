<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalesOrderDetailFee extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_order_fee', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->uuid("sales_order_id");
            $table->uuid("peronel_id");
            $table->double("percentage_fee", 5,2);
            $table->double("shared_fee", 15, 2);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign("sales_order_id")
                  ->references("id")
                  ->on("sales_orders")
                  ->onDelete("cascade");
                  
            $table->foreign("peronel_id")
                  ->references("id")
                  ->on("personels")
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
        Schema::dropIfExists('sales_order_fee');
    }
}
