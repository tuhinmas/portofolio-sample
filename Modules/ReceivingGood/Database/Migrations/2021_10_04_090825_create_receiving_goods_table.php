<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReceivingGoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('receiving_goods', function (Blueprint $table) {
            $table->uuid("id")->unique()->primary();
            $table->uuid("sales_order_id")->foreign("sales_order_id")
                  ->references("id")
                  ->on("sales_orders")
                  ->onUpdate("cascade");
            $table->uuid("dispatch_order_id")->nullable();
            $table->string('shipping_status')->default('dispatch');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('receiving_goods');
    }
}
