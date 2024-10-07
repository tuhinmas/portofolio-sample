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
        Schema::create('delivery_order_numbers', function (Blueprint $table) {
            $table->id();
            $table->uuid("dispatch_order_id")->unique()->nullable();
            $table->uuid("dispatch_promotion_id")->unique()->nullable();
            $table->uuid("delivery_order_id")->unique();
            $table->string("delivery_order_number")->unique();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign("dispatch_order_id")
                ->references("id")
                ->on("discpatch_order")
                ->onDelete("cascade");

            $table->foreign("dispatch_promotion_id")
                ->references("id")
                ->on("dispatch_promotions")
                ->onDelete("cascade");

            $table->foreign("delivery_order_id")
                ->references("id")
                ->on("delivery_orders")
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
        Schema::dropIfExists('delivery_order_numbers');
    }
};
