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
        Schema::create('pickup_order_details', function (Blueprint $table) {
            $table->uuid("id")->unique();
            $table->primary("id");
            $table->uuid("pickup_order_id");
            $table->foreign("pickup_order_id")
                ->references("id")
                ->on("pickup_orders")
                ->onDelete("cascade");
            $table->string("product_name")->nullable();
            $table->string("type")->nullable();
            $table->integer("quantity_unit_load")->nullable();
            $table->integer("quantity_actual_load")->nullable();
            $table->string("unit")->nullable();
            $table->float("weight")->nullable();
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
        Schema::dropIfExists('pickup_order_details');
    }
};
