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
        Schema::create('log_sales_order_returns', function (Blueprint $table) {
            $table->id();
            $table->uuid("sales_order_return");
            $table->uuid("sales_order_affected")->nullable();
            $table->timestamps();

            $table->foreign("sales_order_return")
                ->references("id")
                ->on("sales_orders")
                ->onDelete("cascade");

            $table->foreign("sales_order_affected")
                ->references("id")
                ->on("sales_orders")
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
        Schema::dropIfExists('log_sales_order_returns');
    }
};
