<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_adjustment_stock_to_origins', function (Blueprint $table) {
            $table->uuid("id")->primary()->unique();
            $table->uuid("adjustment_stock_id");
            $table->uuid("sales_order_origin_id");
            $table->timestamps();
            $table->foreign("adjustment_stock_id")
                ->references("id")
                ->on("adjustment_stock") 
                ->onDelete("cascade");

            $table->foreign("sales_order_origin_id")
                ->references("id")
                ->on("sales_order_origins")
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
        Schema::dropIfExists('log_adjustment_stock_to_origins');
    }
};
