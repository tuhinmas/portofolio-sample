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
        Schema::create('delivery_pickup_orders', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->integer("status")->nullable()->comment("1 = Diterima; 2 = Belum Diterima; 3 = dibatalkan");

            $table->uuid("pickup_order_id");
            $table->foreign("pickup_order_id")
                ->references("id")
                ->on("pickup_orders")
                ->onDelete("cascade");
            
            $table->uuid("delivery_order_id");
            $table->enum("delivery_order_type", ["delivery_orders","promotion_goods"])->nullable();

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
        Schema::dropIfExists('delivery_pickup_orders');
    }
};
