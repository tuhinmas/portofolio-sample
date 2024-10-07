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
        Schema::create('delivery_order_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid("personel_id");
            $table->uuid("delivery_order_id");
            $table->string("status")->nullable();
            $table->timestamps();
            $table->foreign("personel_id")
                ->references("id")
                ->on("personels");

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
        Schema::dropIfExists('delivery_order_histories');
    }
};
